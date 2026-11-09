<?php

namespace App\Filament\Imports;

use App\Actions\Suppliers\SaveSupplier;
use App\Enums\CompanyType;
use App\Enums\SupplierSource;
use App\Models\CategoryTranslation;
use App\Models\Supplier;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;
use Illuminate\Validation\Rule;

/**
 * CSV import for supplier leads (e.g. research lists from marketplaces or
 * fair catalogues). Existing suppliers are matched on registration number,
 * then website, then exact name, and updated instead of duplicated.
 * Imported suppliers start as "discovered"; status and verification are
 * never imported.
 */
class SupplierImporter extends Importer
{
    protected static ?string $model = Supplier::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')->requiredMapping()->rules(['required', 'max:190'])->example('Ningbo Example Packaging Co.'),
            ImportColumn::make('name_local')->rules(['nullable', 'max:190']),
            ImportColumn::make('legal_name')->rules(['nullable', 'max:190']),
            ImportColumn::make('registration_number')->rules(['nullable', 'max:64']),
            ImportColumn::make('company_type')->rules(['nullable', Rule::enum(CompanyType::class)])->example('manufacturer'),
            ImportColumn::make('province')->rules(['nullable', 'max:100']),
            ImportColumn::make('city')->rules(['nullable', 'max:100']),
            ImportColumn::make('website')->rules(['nullable', 'url', 'max:255']),
            ImportColumn::make('business_email')->rules(['nullable', 'email', 'max:190']),
            ImportColumn::make('business_phone')->rules(['nullable', 'max:50']),
            ImportColumn::make('moq_min')->integer()->rules(['nullable', 'integer', 'min:1']),
            ImportColumn::make('offers_oem')->boolean()->rules(['nullable', 'boolean']),
            ImportColumn::make('offers_odm')->boolean()->rules(['nullable', 'boolean']),
            ImportColumn::make('source')->rules(['nullable', Rule::enum(SupplierSource::class)])->example('alibaba'),
            ImportColumn::make('source_url')->rules(['nullable', 'url', 'max:500']),
            ImportColumn::make('categories')->label('Categories (slugs separated by |)')
                ->fillRecordUsing(fn () => null)->example('verzenddozen|shipping-boxes'),
        ];
    }

    public function resolveRecord(): Supplier
    {
        $reg = trim((string) ($this->data['registration_number'] ?? ''));
        $web = self::normalizeHost($this->data['website'] ?? null);

        $existing = ($reg !== '' ? Supplier::where('registration_number', $reg)->first() : null)
            ?? ($web ? Supplier::whereNotNull('website')->get(['id', 'website'])->first(fn ($s) => self::normalizeHost($s->website) === $web) : null)
            ?? Supplier::where('name', trim((string) $this->data['name']))->first();

        return $existing ?? new Supplier;
    }

    /** Filling happens in saveRecord() through SaveSupplier (blank cells never overwrite data). */
    public function fillRecord(): void {}

    public function saveRecord(): void
    {
        /** @var Supplier $record */
        $record = $this->record;
        $attributes = collect($this->data)->only([
            'name', 'name_local', 'legal_name', 'registration_number', 'company_type', 'province', 'city', 'website',
            'business_email', 'business_phone', 'moq_min', 'offers_oem', 'offers_odm', 'source', 'source_url',
        ])->reject(fn ($v) => $v === null || $v === '')->all();

        $categoryIds = null;
        if (filled($this->data['categories'] ?? null)) {
            $slugs = array_filter(array_map('trim', explode('|', (string) $this->data['categories'])));
            $categoryIds = CategoryTranslation::whereIn('slug', $slugs)->pluck('category_id')->unique()->values()->all();
            if ($record->exists) {
                $categoryIds = array_values(array_unique([...$record->categories()->pluck('categories.id')->all(), ...$categoryIds]));
            }
        }

        $attributes['assigned_to'] = $record->assigned_to ?? $this->import->user_id;

        $this->record = app(SaveSupplier::class)->handle($record->exists ? $record : null, $attributes, [], $categoryIds);
    }

    private static function normalizeHost(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }
        $host = parse_url(str_contains($url, '://') ? $url : 'https://'.$url, PHP_URL_HOST);

        return $host ? preg_replace('/^www\./', '', strtolower($host)) : null;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Supplier import completed: '.Number::format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported or updated.';

        if ($failed = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failed).' '.str('row')->plural($failed).' failed — download the failed rows to fix them.';
        }

        return $body;
    }
}
