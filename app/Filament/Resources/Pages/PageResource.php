<?php

namespace App\Filament\Resources\Pages;

use App\Enums\PublicationStatus;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Models\Page;
use App\Support\Locales;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(['default' => 1, 'lg' => 3])->components([
            Tabs::make('Translations')->columnSpan(['default' => 1, 'lg' => 2])->tabs(
                collect(Locales::all())->map(fn (string $name, string $locale) => Tab::make($name)->schema([
                    TextInput::make("translations.$locale.title")->label('Title')->required($locale === Locales::default())->maxLength(190),
                    TextInput::make("translations.$locale.slug")->label('URL slug')->maxLength(190)
                        ->rule('nullable')->rule('regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                        ->helperText('Leave empty to generate from the title.'),
                    RichEditor::make("translations.$locale.body")->label('Content')
                        ->toolbarButtons([['bold', 'italic', 'link'], ['h2', 'h3'], ['bulletList', 'orderedList', 'blockquote'], ['undo', 'redo']]),
                    TextInput::make("translations.$locale.seo_title")->label('SEO title')->maxLength(70),
                    Textarea::make("translations.$locale.seo_description")->label('SEO description')->rows(2)->maxLength(320),
                ]))->values()->all()
            ),
            Section::make('Settings')->columnSpan(['default' => 1, 'lg' => 1])->schema([
                TextInput::make('key')->required()->maxLength(100)->alphaDash()
                    ->unique(ignoreRecord: true)
                    ->disabled(fn (?Page $record) => $record !== null)
                    ->helperText('Stable identifier used by the code (e.g. "privacy"). Cannot be changed later.'),
                Select::make('status')->options(PublicationStatus::class)->default(PublicationStatus::Draft->value)->required(),
                Toggle::make('show_in_footer')->label('Show in footer'),
                Toggle::make('requires_legal_review')->label('Draft: not yet reviewed by a lawyer')
                    ->helperText('Shows a visible "draft" notice on the public page.'),
                TextInput::make('sort_order')->numeric()->default(0),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $locale = Locales::default();

        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('translations'))
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('title')->state(fn (Page $r) => $r->translated('title', $locale)),
                TextColumn::make('key')->badge()->color('gray'),
                TextColumn::make('status')->badge(),
                IconColumn::make('requires_legal_review')->label('Legal review pending')->boolean()
                    ->trueIcon(Heroicon::OutlinedExclamationTriangle)->trueColor('warning')->falseIcon(Heroicon::OutlinedCheck)->falseColor('success'),
                IconColumn::make('show_in_footer')->label('Footer')->boolean(),
                TextColumn::make('updated_at')->since(),
            ])
            ->recordActions([
                Action::make('view_public')->label('View')->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->visible(fn (Page $r) => $r->status === PublicationStatus::Active)
                    ->url(fn (Page $r) => ($s = $r->translations->firstWhere('locale', $locale)?->slug)
                        ? localized_route('pages.show', ['slug' => $s], $locale) : null, shouldOpenInNewTab: true),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}
