<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Enums\PublicationStatus;
use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Category;
use App\Support\Locales;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\File;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'lg' => 3])
            ->components([
                Tabs::make('Translations')
                    ->columnSpan(['default' => 1, 'lg' => 2])
                    ->tabs(collect(Locales::all())->map(fn (string $name, string $locale) => Tab::make($name)
                        ->schema(self::translationFields($locale)))->values()->all()),

                Section::make('Settings')
                    ->columnSpan(['default' => 1, 'lg' => 1])
                    ->schema([
                        Select::make('parent_id')
                            ->label('Parent category')
                            ->placeholder('— Top level —')
                            ->options(fn (?Category $record) => CategoryResource::treeOptions($record))
                            ->searchable()
                            ->default(fn () => request()->integer('parent') ?: null),
                        Select::make('status')
                            ->options(PublicationStatus::class)
                            ->default(PublicationStatus::Draft->value)
                            ->required()
                            ->helperText('Only active categories (with active parents) are public.'),
                        Toggle::make('is_featured')->label('Featured on homepage'),
                        Select::make('icon')
                            ->options(self::iconOptions())
                            ->searchable()
                            ->helperText('Heroicons outline set.'),
                        FileUpload::make('image_path')
                            ->label('Image')
                            ->image()
                            ->disk('public')
                            ->directory('categories')
                            ->visibility('public')
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp']),
                    ]),
            ]);
    }

    /**
     * @return list<Component|Field>
     */
    private static function translationFields(string $locale): array
    {
        $isDefault = $locale === Locales::default();

        return [
            TextInput::make("translations.$locale.name")
                ->label('Name')
                ->required($isDefault)
                ->maxLength(190)
                ->helperText($isDefault ? 'Required (default language).' : 'Leave empty to fall back to the default language.'),
            TextInput::make("translations.$locale.slug")
                ->label('URL slug')
                ->maxLength(190)
                ->rule('nullable')
                ->rule('regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                ->helperText('Leave empty to generate from the name. Must be unique per language.'),
            Textarea::make("translations.$locale.description")->label('Description')->rows(3)->maxLength(2000),
            TextInput::make("translations.$locale.seo_title")->label('SEO title')->maxLength(70),
            Textarea::make("translations.$locale.seo_description")->label('SEO description')->rows(2)->maxLength(320),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function iconOptions(): array
    {
        return collect(File::files(base_path('vendor/blade-ui-kit/blade-heroicons/resources/svg')))
            ->map(fn ($f) => $f->getFilenameWithoutExtension())
            ->filter(fn (string $n) => str_starts_with($n, 'o-'))
            ->sort()
            ->mapWithKeys(fn (string $n) => ['heroicon-'.$n => substr($n, 2)])
            ->all();
    }
}
