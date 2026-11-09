<?php

namespace App\Filament\Pages;

use App\Actions\Suppliers\TransitionPipelineStatus;
use App\Enums\Permission;
use App\Enums\SupplierPipelineStatus;
use App\Filament\Resources\Suppliers\SupplierResource;
use App\Models\Supplier;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * Kanban view of the supplier pipeline. Moving a card runs the same
 * TransitionPipelineStatus action (and its rules) as the edit page.
 */
class SupplierPipeline extends Page
{
    protected string $view = 'filament.pages.supplier-pipeline';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedViewColumns;

    protected static string|UnitEnum|null $navigationGroup = 'Suppliers';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Pipeline';

    public const CARDS_PER_COLUMN = 40;

    #[Url]
    public bool $mine = false;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can(Permission::ViewSuppliers->value);
    }

    /**
     * @return array<string, array{status: SupplierPipelineStatus, count: int, suppliers: Collection<int, Supplier>}>
     */
    public function getColumns(): array
    {
        $base = Supplier::query()->when($this->mine, fn ($query) => $query->where('assigned_to', auth()->id()));
        $counts = (clone $base)->selectRaw('pipeline_status, count(*) as c')->groupBy('pipeline_status')->pluck('c', 'pipeline_status');

        return collect(SupplierPipelineStatus::board())->mapWithKeys(fn (SupplierPipelineStatus $status) => [$status->value => [
            'status' => $status,
            'count' => (int) ($counts[$status->value] ?? 0),
            'suppliers' => (clone $base)->where('pipeline_status', $status)->with('assignee')
                ->orderByRaw('next_follow_up_at is null')->orderBy('next_follow_up_at')->latest('updated_at')
                ->limit(self::CARDS_PER_COLUMN)->get(),
        ]])->all();
    }

    public function move(int $supplierId, string $status): void
    {
        $supplier = Supplier::findOrFail($supplierId);
        $to = SupplierPipelineStatus::from($status);

        try {
            app(TransitionPipelineStatus::class)->handle($supplier, $to, auth()->user());
            Notification::make()->title($supplier->name.' → '.$to->getLabel())->success()->send();
        } catch (ValidationException $e) {
            Notification::make()->title('Not moved')->body(collect($e->errors())->flatten()->implode(' '))->danger()->send();
        } catch (AuthorizationException $e) {
            Notification::make()->title('Not allowed')->body($e->getMessage() ?: 'You cannot move suppliers to this status.')->danger()->send();
        }
    }

    public function editUrl(Supplier $supplier): string
    {
        return SupplierResource::getUrl('edit', ['record' => $supplier]);
    }
}
