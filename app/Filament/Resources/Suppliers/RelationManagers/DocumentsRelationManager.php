<?php

namespace App\Filament\Resources\Suppliers\RelationManagers;

use App\Actions\Suppliers\StoreSupplierDocument;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\Permission;
use App\Filament\Resources\Suppliers\RelationManagers\Concerns\AuthorizesSupplierRelation;
use App\Models\Supplier;
use App\Models\SupplierDocument;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class DocumentsRelationManager extends RelationManager
{
    use AuthorizesSupplierRelation;

    protected static string $relationship = 'documents';

    protected static function writePermission(): Permission
    {
        return Permission::ManageVerification;
    }

    public function table(Table $table): Table
    {
        return $table
            ->description('Stored privately. Files open only for authorised staff and the supplier who uploaded them.')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('type')->badge()->color('gray'),
                TextColumn::make('original_name')->label('File')->limit(40)
                    ->url(fn (SupplierDocument $d) => route('documents.download', $d), shouldOpenInNewTab: true),
                TextColumn::make('status')->badge(),
                TextColumn::make('valid_until')->date()->placeholder('—'),
                TextColumn::make('size')->formatStateUsing(fn (int $state) => number_format($state / 1024, 0).' KB'),
                TextColumn::make('created_at')->label('Uploaded')->since(),
            ])
            ->filters([SelectFilter::make('type')->options(DocumentType::class), SelectFilter::make('status')->options(DocumentStatus::class)])
            ->headerActions([
                Action::make('upload')->label('Upload document')->icon(Heroicon::OutlinedArrowUpTray)
                    ->hidden(fn () => $this->isReadOnly())
                    ->schema([
                        Select::make('type')->options(DocumentType::class)->required(),
                        FileUpload::make('file')->required()->storeFiles(false)->maxSize(10240)
                            ->acceptedFileTypes(array_keys(StoreSupplierDocument::ALLOWED)),
                        DatePicker::make('valid_until'),
                    ])
                    ->action(function (array $data) {
                        /** @var Supplier $supplier */
                        $supplier = $this->getOwnerRecord();
                        try {
                            app(StoreSupplierDocument::class)->handle($supplier, $data['file'], DocumentType::from($data['type'] instanceof DocumentType ? $data['type']->value : $data['type']), auth()->user(), $data['valid_until'] ?? null);
                            Notification::make()->title('Document uploaded')->success()->send();
                        } catch (ValidationException $e) {
                            Notification::make()->title('Upload rejected')->body(collect($e->errors())->flatten()->implode(' '))->danger()->send();
                        }
                    }),
            ])
            ->recordActions([
                Action::make('review')->icon(Heroicon::OutlinedCheckBadge)
                    ->hidden(fn () => $this->isReadOnly())
                    ->fillForm(fn (SupplierDocument $d) => ['status' => $d->status->value, 'valid_until' => $d->valid_until, 'review_notes' => $d->review_notes])
                    ->schema([
                        Select::make('status')->options(DocumentStatus::class)->required(),
                        DatePicker::make('valid_until'),
                        Textarea::make('review_notes')->rows(3),
                    ])
                    ->action(function (SupplierDocument $d, array $data) {
                        $d->fill(['status' => $data['status'], 'valid_until' => $data['valid_until'] ?? null, 'review_notes' => $data['review_notes'] ?? null]);
                        $d->forceFill(['reviewed_by' => auth()->id(), 'reviewed_at' => now()])->save();
                    }),
                Action::make('delete')->icon(Heroicon::OutlinedTrash)->color('danger')->requiresConfirmation()
                    ->hidden(fn () => $this->isReadOnly())
                    ->action(fn (SupplierDocument $d) => app(StoreSupplierDocument::class)->delete($d)),
            ]);
    }
}
