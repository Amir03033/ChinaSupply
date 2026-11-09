<?php

namespace App\Filament\Resources\SourcingRequests\RelationManagers;

use App\Actions\Sourcing\ChangeRequestStatus;
use App\Actions\Sourcing\RecordRequestEvent;
use App\Enums\MatchStatus;
use App\Enums\SourcingRequestStatus;
use App\Enums\SupplierPipelineStatus;
use App\Filament\Resources\SourcingRequests\RelationManagers\Concerns\OwnedBySourcingRequest;
use App\Filament\Resources\Suppliers\SupplierResource;
use App\Models\SourcingRequestMatch;
use App\Models\Supplier;
use App\Services\SupplierMatcher;
use App\Support\Money;
use App\Support\SourcingOptions;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MatchesRelationManager extends RelationManager
{
    use OwnedBySourcingRequest;

    protected static string $relationship = 'matches';

    protected static ?string $title = 'Supplier matches';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('status')->options(MatchStatus::class)->required(),
            Toggle::make('shared_with_buyer')->label('Visible to buyer')->helperText('Use "Share shortlist" to notify the buyer.')
                ->disabled(fn (?SourcingRequestMatch $record) => $record !== null && ! $record->supplier->isPubliclyVisible()),
            Section::make('Quote summary (from the supplier)')->columns(3)->columnSpanFull()->schema([
                TextInput::make('unit_price_cents')->label('Unit price')->numeric()->minValue(0)
                    ->formatStateUsing(fn ($state) => Money::centsToDecimal($state))->dehydrateStateUsing(fn ($state) => Money::decimalToCents($state)),
                Select::make('currency')->options(['USD' => 'USD', 'EUR' => 'EUR', 'CNY' => 'CNY'])->default('USD'),
                Select::make('incoterm')->options(array_combine(SourcingOptions::INCOTERMS, SourcingOptions::INCOTERMS)),
                TextInput::make('moq')->label('MOQ')->numeric()->minValue(1),
                TextInput::make('lead_time_days')->label('Lead time (days)')->numeric()->minValue(1),
                TextInput::make('sample_cost_cents')->label('Sample cost')->numeric()->minValue(0)
                    ->formatStateUsing(fn ($state) => Money::centsToDecimal($state))->dehydrateStateUsing(fn ($state) => Money::decimalToCents($state)),
                TextInput::make('payment_terms')->maxLength(190)->columnSpan(2),
                DatePicker::make('quote_valid_until'),
            ]),
            Textarea::make('advice')->label('Advice for the buyer (shown when shared)')->rows(3)->columnSpanFull()->maxLength(2000),
            Textarea::make('internal_notes')->label('Internal notes (never shown)')->rows(3)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('supplier'))
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('supplier.name')->label('Supplier')
                    ->description(fn (SourcingRequestMatch $record) => 'Level '.$record->supplier->verification_level.' · '.$record->supplier->pipeline_status->getLabel())
                    ->url(fn (SourcingRequestMatch $record) => SupplierResource::getUrl('edit', ['record' => $record->supplier_id]), shouldOpenInNewTab: true),
                TextColumn::make('status')->badge(),
                TextColumn::make('unit_price_cents')->label('Price')
                    ->formatStateUsing(fn (SourcingRequestMatch $record) => Money::format($record->unit_price_cents, $record->currency ?? 'USD'))->placeholder('—'),
                TextColumn::make('moq')->label('MOQ')->placeholder('—'),
                TextColumn::make('lead_time_days')->label('Lead time')->suffix(' d')->placeholder('—'),
                IconColumn::make('shared_with_buyer')->label('Shared')->boolean(),
            ])
            ->headerActions([
                Action::make('add')->label('Add supplier')->icon(Heroicon::OutlinedPlus)
                    ->hidden(fn () => $this->isReadOnly())
                    ->schema([
                        Select::make('supplier_id')->label('Supplier')->required()->searchable()
                            ->helperText('Suggestions are listed first; type to search all suppliers.')
                            ->options(fn () => app(SupplierMatcher::class)->suggest($this->sourcingRequest(), 15)
                                ->mapWithKeys(fn ($s) => [$s['supplier']->id => $s['supplier']->name.' — '.implode(', ', $s['reasons'])])->all())
                            ->getSearchResultsUsing(fn (string $search) => Supplier::query()
                                ->whereNotIn('pipeline_status', [SupplierPipelineStatus::Rejected])
                                ->whereNotIn('id', $this->sourcingRequest()->matches()->pluck('supplier_id'))
                                ->where(fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('name_local', 'like', "%{$search}%"))
                                ->limit(30)->get()
                                ->mapWithKeys(fn (Supplier $s) => [$s->id => $s->name.' — L'.$s->verification_level.' · '.($s->city ?? '—')])->all())
                            ->getOptionLabelUsing(fn ($value) => Supplier::find($value)?->name),
                    ])
                    ->action(function (array $data) {
                        $request = $this->sourcingRequest();
                        $match = $request->matches()->firstOrCreate(['supplier_id' => $data['supplier_id']], ['status' => MatchStatus::Suggested, 'added_by' => auth()->id()]);
                        RecordRequestEvent::record($request, 'match_added', ['supplier' => $match->supplier->name], auth()->user(), false);
                    }),
                Action::make('suggest')->label('Add top suggestions')->icon(Heroicon::OutlinedSparkles)->color('gray')
                    ->hidden(fn () => $this->isReadOnly())
                    ->requiresConfirmation()
                    ->modalDescription('Adds up to 5 suggested suppliers (rule-based: category, MOQ fit, OEM/ODM, verification level). Review them before contacting.')
                    ->action(function () {
                        $request = $this->sourcingRequest();
                        $added = 0;
                        foreach (app(SupplierMatcher::class)->suggest($request, 5) as $s) {
                            $request->matches()->firstOrCreate(['supplier_id' => $s['supplier']->id], ['status' => MatchStatus::Suggested, 'added_by' => auth()->id()]);
                            $added++;
                        }
                        Notification::make()->title($added > 0 ? "$added suppliers added" : 'No suitable suppliers found')->send();
                    }),
            ])
            ->recordActions([
                Action::make('contacted')->label('Mark contacted')->icon(Heroicon::OutlinedEnvelope)->color('gray')
                    ->visible(fn (SourcingRequestMatch $record) => $record->status === MatchStatus::Suggested && ! $this->isReadOnly())
                    ->action(function (SourcingRequestMatch $record) {
                        $record->update(['status' => MatchStatus::Contacted, 'contacted_at' => now()]);
                        if ($this->sourcingRequest()->status->value === 'in_review' || $this->sourcingRequest()->status->value === 'submitted') {
                            app(ChangeRequestStatus::class)->handle($this->sourcingRequest(), SourcingRequestStatus::Sourcing, auth()->user());
                        }
                    }),
                EditAction::make()->label('Edit / quote')
                    ->mutateDataUsing(function (array $data, SourcingRequestMatch $record) {
                        if ($data['shared_with_buyer'] ?? false) {
                            $data['shared_with_buyer'] = $record->supplier->isPubliclyVisible();
                        }
                        if (($data['status'] ?? null) === MatchStatus::Quoted->value && $record->responded_at === null) {
                            $data['responded_at'] = now();
                        }

                        return $data;
                    }),
                DeleteAction::make(),
            ]);
    }
}
