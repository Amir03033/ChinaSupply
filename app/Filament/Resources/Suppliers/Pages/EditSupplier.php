<?php

namespace App\Filament\Resources\Suppliers\Pages;

use App\Actions\Suppliers\SaveSupplier;
use App\Actions\Suppliers\TransitionPipelineStatus;
use App\Enums\CommunicationChannel;
use App\Enums\Permission;
use App\Enums\SupplierPipelineStatus;
use App\Filament\Resources\Suppliers\SupplierResource;
use App\Models\OutreachTemplate;
use App\Models\Supplier;
use App\Models\SupplierContact;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditSupplier extends EditRecord
{
    protected static string $resource = SupplierResource::class;

    public function getSubheading(): ?string
    {
        /** @var Supplier $s */
        $s = $this->getRecord();

        return sprintf('Status: %s · Verification level %d (%s)%s',
            $s->pipeline_status->getLabel(), $s->verification_level, __('verification.level_names.'.$s->verification_level),
            $s->verification_review_required ? ' · needs re-verification' : '');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Supplier $s */
        $s = $this->getRecord();
        $data['translations'] = $s->translations()->get()->mapWithKeys(fn ($t) => [$t->locale => $t->only(['description', 'capabilities'])])->all();
        $data['category_ids'] = $s->categories()->pluck('categories.id')->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Supplier $record */
        $translations = $data['translations'] ?? [];
        $categoryIds = $data['category_ids'] ?? [];
        unset($data['translations'], $data['category_ids']);

        return app(SaveSupplier::class)->handle($record, $data, $translations, $categoryIds);
    }

    protected function getHeaderActions(): array
    {
        /** @var Supplier $supplier */
        $supplier = $this->getRecord();

        return [
            Action::make('change_status')
                ->label('Change status')
                ->icon(Heroicon::OutlinedArrowsRightLeft)
                ->visible(fn () => auth()->user()?->can(Permission::ManageSuppliers->value))
                ->schema([
                    Select::make('status')->options(SupplierPipelineStatus::class)->required()->live()
                        ->default($supplier->pipeline_status->value),
                    Textarea::make('reason')->label('Rejection reason')->rows(2)
                        ->visible(fn (Get $get) => $get('status') === SupplierPipelineStatus::Rejected->value),
                ])
                ->action(function (array $data) use ($supplier) {
                    try {
                        $to = $data['status'] instanceof SupplierPipelineStatus ? $data['status'] : SupplierPipelineStatus::from($data['status']);
                        app(TransitionPipelineStatus::class)->handle($supplier, $to, auth()->user(), $data['reason'] ?? null);
                        Notification::make()->title('Status updated')->success()->send();
                        $this->refreshFormData(['pipeline_status']);
                    } catch (ValidationException $e) {
                        Notification::make()->title('Status not changed')->body(collect($e->errors())->flatten()->implode(' '))->danger()->send();
                    }
                }),
            Action::make('outreach')
                ->label('Log outreach')
                ->icon(Heroicon::OutlinedEnvelope)
                ->visible(fn () => auth()->user()?->can(Permission::ManageSuppliers->value))
                ->modalDescription('Pick a template, copy the text into your mailbox, WeChat or marketplace chat, send it there, then save to log it here.')
                ->schema([
                    Select::make('template_id')->label('Template')
                        ->options(fn () => OutreachTemplate::where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                        ->live()
                        ->afterStateUpdated(function (?string $state, Set $set, Get $get) use ($supplier) {
                            $template = $state ? OutreachTemplate::find($state) : null;
                            if ($template) {
                                $rendered = $template->render($supplier, $get('contact_id') ? SupplierContact::find($get('contact_id')) : $supplier->contacts()->where('is_primary', true)->first(), auth()->user());
                                $set('subject', $rendered['subject']);
                                $set('body', $rendered['body']);
                                $set('channel', $template->channel->value);
                            }
                        }),
                    Select::make('contact_id')->label('Contact')->options(fn () => $supplier->contacts()->pluck('name', 'id')),
                    Select::make('channel')->options(CommunicationChannel::class)->required()->default(CommunicationChannel::Email->value),
                    TextInput::make('subject')->maxLength(255),
                    Textarea::make('body')->rows(10)->required(),
                    DateTimePicker::make('follow_up_at')->label('Create follow-up task for')->default(now()->addDays(4)->setTime(9, 0)),
                ])
                ->action(function (array $data) use ($supplier) {
                    $supplier->communications()->create([
                        'contact_id' => $data['contact_id'] ?? null,
                        'outreach_template_id' => $data['template_id'] ?? null,
                        'channel' => $data['channel'],
                        'direction' => 'outbound',
                        'subject' => $data['subject'] ?? null,
                        'body' => $data['body'],
                        'user_id' => auth()->id(),
                        'occurred_at' => now(),
                    ]);
                    if (filled($data['follow_up_at'] ?? null)) {
                        $supplier->tasks()->create([
                            'title' => 'Follow up: '.($data['subject'] ?? 'outreach'),
                            'due_at' => $data['follow_up_at'],
                            'assigned_to' => $supplier->assigned_to ?? auth()->id(),
                            'created_by' => auth()->id(),
                        ]);
                    }
                    if (in_array($supplier->pipeline_status, [SupplierPipelineStatus::Discovered, SupplierPipelineStatus::Researching], true)) {
                        $supplier->pipeline_status = SupplierPipelineStatus::Contacted;
                        $supplier->save();
                    }
                    Notification::make()->title('Outreach logged')->success()->send();
                }),
            ActionGroup::make([
                Action::make('view_public')->label('View public profile')->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->visible($supplier->isPubliclyVisible())
                    ->url(fn () => localized_route('suppliers.show', ['slug' => $supplier->slug], 'nl'), shouldOpenInNewTab: true),
                DeleteAction::make(),
            ]),
        ];
    }
}
