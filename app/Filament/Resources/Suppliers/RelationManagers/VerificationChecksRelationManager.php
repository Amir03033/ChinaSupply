<?php

namespace App\Filament\Resources\Suppliers\RelationManagers;

use App\Actions\Suppliers\SaveSupplier;
use App\Actions\Suppliers\StoreSupplierDocument;
use App\Actions\Verification\RecordVerificationCheck;
use App\Enums\DocumentType;
use App\Enums\Permission;
use App\Enums\VerificationCheckStatus;
use App\Filament\Resources\Suppliers\RelationManagers\Concerns\AuthorizesSupplierRelation;
use App\Models\Supplier;
use App\Models\VerificationCheck;
use App\Services\VerificationLevelCalculator;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class VerificationChecksRelationManager extends RelationManager
{
    use AuthorizesSupplierRelation;

    protected static string $relationship = 'verificationChecks';

    protected static ?string $title = 'Verification';

    protected static function writePermission(): Permission
    {
        return Permission::ManageVerification;
    }

    public function mount(): void
    {
        parent::mount();
        /** @var Supplier $supplier */
        $supplier = $this->getOwnerRecord();
        SaveSupplier::ensureCheckRows($supplier);
    }

    public function table(Table $table): Table
    {
        $levelFor = function (VerificationCheck $c): ?int {
            foreach (VerificationLevelCalculator::REQUIREMENTS as $level => $types) {
                if (in_array($c->type, $types, true)) {
                    return $level;
                }
            }

            return null;
        };

        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['reviewer', 'evidence']))
            ->description('The level is calculated from these checks only. A check counts while it is passed and not expired.')
            ->paginated(false)
            ->columns([
                TextColumn::make('type')->label('Check')->formatStateUsing(fn ($state) => $state->getLabel())
                    ->description(fn (VerificationCheck $c) => ($l = $levelFor($c)) ? "Required for level $l" : 'Shown separately'),
                TextColumn::make('status')->badge()
                    ->formatStateUsing(fn (VerificationCheck $c) => $c->isExpired() ? 'Expired' : $c->status->getLabel())
                    ->color(fn (VerificationCheck $c) => $c->isExpired() ? 'danger' : $c->status->getColor()),
                TextColumn::make('method')->label('How checked (public)')->limit(60)->placeholder('—')->wrap(),
                TextColumn::make('checked_at')->date()->placeholder('—'),
                TextColumn::make('expires_at')->date()->placeholder('—')
                    ->color(fn (VerificationCheck $c) => $c->expires_at?->lessThan(now()->addDays(30)) ? 'warning' : null),
                TextColumn::make('reviewer.name')->label('Reviewer')->placeholder('—'),
                TextColumn::make('evidence.original_name')->label('Evidence')->placeholder('—')
                    ->url(fn (VerificationCheck $c) => $c->evidence ? route('documents.download', $c->evidence) : null, shouldOpenInNewTab: true),
            ])
            ->recordActions([
                Action::make('record')
                    ->label('Record result')
                    ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                    ->hidden(fn () => $this->isReadOnly())
                    ->fillForm(fn (VerificationCheck $c) => [
                        'status' => $c->status->value,
                        'method' => $c->method,
                        'internal_notes' => $c->internal_notes,
                        'evidence_document_id' => $c->evidence_document_id,
                    ])
                    ->schema(fn (VerificationCheck $c) => [
                        Select::make('status')->options(VerificationCheckStatus::class)->required(),
                        Textarea::make('method')->label('How was this checked? (shown to buyers)')->rows(2)->maxLength(500)
                            ->helperText('E.g. "Business licence compared with the national enterprise credit register on 14 March 2026."'),
                        Select::make('evidence_document_id')->label('Evidence document')
                            ->options(fn () => $this->supplier()->documents()->latest()->get()
                                ->mapWithKeys(fn ($d) => [$d->id => $d->type->getLabel().' — '.$d->original_name])),
                        FileUpload::make('evidence_upload')->label('…or upload new evidence (private)')
                            ->storeFiles(false)->maxSize(10240)->acceptedFileTypes(array_keys(StoreSupplierDocument::ALLOWED)),
                        DateTimePicker::make('expires_at')->label('Valid until')
                            ->helperText($c->type->defaultValidityMonths() ? 'Leave empty for the default of '.$c->type->defaultValidityMonths().' months.' : 'Leave empty if it does not expire.'),
                        Textarea::make('internal_notes')->label('Internal notes (never public)')->rows(3),
                    ])
                    ->action(function (VerificationCheck $c, array $data) {
                        /** @var Supplier $supplier */
                        $supplier = $this->getOwnerRecord();
                        try {
                            if (($upload = $data['evidence_upload'] ?? null) instanceof TemporaryUploadedFile || $upload instanceof UploadedFile) {
                                $data['evidence_document_id'] = app(StoreSupplierDocument::class)
                                    ->handle($supplier, $upload, DocumentType::Evidence, auth()->user())->id;
                            }
                            app(RecordVerificationCheck::class)->handle($supplier, $c->type, $data, auth()->user());
                            $supplier->refresh();
                            Notification::make()->title('Check recorded')->body('Verification level is now '.$supplier->verification_level.'.')->success()->send();
                        } catch (ValidationException $e) {
                            Notification::make()->title('Not saved')->body(collect($e->errors())->flatten()->implode(' '))->danger()->send();
                        }
                    }),
            ]);
    }
}
