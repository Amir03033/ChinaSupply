<?php

namespace App\Filament\Resources\Suppliers\Schemas;

use App\Enums\CompanyType;
use App\Enums\SupplierSource;
use App\Enums\UserType;
use App\Filament\Resources\Categories\CategoryResource;
use App\Models\User;
use App\Support\Locales;
use App\Support\SupplierOptions;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class SupplierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(['default' => 1, 'lg' => 3])->components([
            Grid::make(1)->columnSpan(['default' => 1, 'lg' => 2])->schema([
                Section::make('Company identity')->columns(2)->schema([
                    TextInput::make('name')->label('Display name (Latin script)')->required()->maxLength(190),
                    TextInput::make('name_local')->label('Name in Chinese')->maxLength(190),
                    TextInput::make('legal_name')->label('Registered legal name')->maxLength(190),
                    TextInput::make('registration_number')->label('Registration number (USCC)')
                        ->maxLength(64)->unique(ignoreRecord: true)
                        ->helperText('Unified Social Credit Code: 18 characters for Chinese companies.'),
                    Select::make('company_type')->options(CompanyType::class)->default(CompanyType::Unknown->value)->required(),
                    TextInput::make('founded_year')->numeric()->minValue(1900)->maxValue((int) date('Y')),
                    Select::make('employee_range')->options(SupplierOptions::employeeRanges()),
                    TextInput::make('website')->url()->maxLength(255),
                ]),
                Section::make('Location & business contact')->description('Address and contact details are private unless “show contact publicly” is on.')->columns(2)->schema([
                    Select::make('country')->options(SupplierOptions::countries())->default('CN')->required(),
                    TextInput::make('province')->maxLength(100),
                    TextInput::make('city')->maxLength(100),
                    Textarea::make('address')->rows(2)->columnSpanFull(),
                    TextInput::make('business_email')->email()->maxLength(190),
                    TextInput::make('business_phone')->tel()->maxLength(50),
                    Toggle::make('show_contact_publicly')->columnSpanFull(),
                ]),
                Section::make('Capabilities')->columns(2)->schema([
                    TextInput::make('moq_min')->label('Minimum order quantity')->numeric()->minValue(1),
                    TextInput::make('moq_unit')->label('MOQ unit')->placeholder('pieces, sets, metres…')->maxLength(30),
                    TextInput::make('production_capacity')->placeholder('e.g. 50,000 pcs/month')->maxLength(190),
                    Grid::make(2)->schema([
                        TextInput::make('lead_time_min_days')->label('Lead time from (days)')->numeric()->minValue(1),
                        TextInput::make('lead_time_max_days')->label('to (days)')->numeric()->minValue(1)->gte('lead_time_min_days'),
                    ]),
                    Toggle::make('offers_oem')->label('OEM'),
                    Toggle::make('offers_odm')->label('ODM'),
                    Toggle::make('offers_private_label')->label('Private label'),
                    CheckboxList::make('export_markets')->options(SupplierOptions::exportMarkets())->columns(3)->columnSpanFull(),
                    CheckboxList::make('languages')->options(SupplierOptions::languages())->columns(4)->columnSpanFull(),
                ]),
                Tabs::make('Descriptions')->tabs(collect(Locales::all())->map(fn ($name, $locale) => Tab::make($name)->schema([
                    Textarea::make("translations.$locale.description")->label('Company description')->rows(5)->maxLength(3000),
                    Textarea::make("translations.$locale.capabilities")->label('Manufacturing capabilities')->rows(4)->maxLength(3000),
                ]))->values()->all()),
            ]),
            Grid::make(1)->columnSpan(['default' => 1, 'lg' => 1])->schema([
                Section::make('CRM')->schema([
                    Select::make('source')->options(SupplierSource::class)->default(SupplierSource::Other->value)->required(),
                    TextInput::make('source_url')->url()->maxLength(500),
                    Select::make('assigned_to')->label('Owner')
                        ->options(fn () => User::where('type', UserType::Staff)->orderBy('name')->pluck('name', 'id'))
                        ->searchable(),
                ]),
                Section::make('Categories')->schema([
                    Select::make('category_ids')
                        ->label('Categories')
                        ->multiple()
                        ->options(fn () => CategoryResource::treeOptions())
                        ->searchable()
                        ->helperText('Choose the most specific categories that apply.'),
                ]),
                Section::make('Media')->schema([
                    FileUpload::make('logo_path')->label('Logo')->image()->disk('public')->directory('suppliers/logos')
                        ->visibility('public')->maxSize(1024)->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp']),
                    FileUpload::make('cover_path')->label('Cover image')->image()->disk('public')->directory('suppliers/covers')
                        ->visibility('public')->maxSize(3072)->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp']),
                ]),
            ]),
        ]);
    }
}
