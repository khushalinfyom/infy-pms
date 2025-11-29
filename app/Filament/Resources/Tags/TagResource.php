<?php

namespace App\Filament\Resources\Tags;

use App\Filament\Resources\Tags\Pages\ManageTags;
use App\Models\Tag;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TagResource extends Resource
{
    protected static ?string $model = Tag::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    public static function getNavigationGroup(): ?string
    {
        return __('messages.settings.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.settings.tags');
    }

    public static function getLabel(): ?string
    {
        return __('messages.settings.tags');
    }

    protected static ?string $recordTitleAttribute = 'Tag';

    public static function canViewAny(): bool
    {
        return authUserHasPermission('manage_tags');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([

                Hidden::make('created_by')
                    ->default(auth()->user()->id),

                TextInput::make('name')
                    ->label(__('messages.common.name'))
                    ->placeholder(__('messages.common.name'))
                    ->required()
                    ->unique(),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordAction(null)
            ->paginated([10, 25, 50, 100])
            ->defaultSort('id', 'desc')
            ->recordActionsColumnLabel(__('messages.common.action'))
            ->emptyStateHeading(function ($livewire) {
                if (empty($livewire->tableSearch)) {
                    return __('messages.common.empty_table_heading', ['table' => 'Tags']);
                } else {
                    return __('messages.common.empty_table_search_heading', ['table' => 'Tags', 'search' => $livewire->tableSearch]);
                }
            })
            ->recordTitleAttribute('Tag')
            ->columns([
                TextColumn::make('name')
                    ->label(__('messages.common.name'))
                    ->sortable()
                    ->searchable(),
            ])
            ->recordActions([

                EditAction::make()
                    ->tooltip(__('messages.common.edit'))
                    ->iconButton()
                    ->modalWidth('md')
                    ->modalHeading(__('messages.settings.edit_tag'))
                    ->successNotificationTitle(__('messages.settings.tag_updated_successfully')),

                \App\Filament\Actions\CustomDeleteAction::make()
                    ->setCommonProperties()
                    ->iconButton()
                    ->tooltip(__('messages.common.delete'))
                    ->modalHeading(__('messages.settings.delete_tag'))
                    ->successNotificationTitle(__('messages.settings.tag_deleted_successfully')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    \App\Filament\Actions\CustomDeleteBulkAction::make()
                        ->setCommonProperties()
                        ->modalHeading(__('messages.settings.delete_tags'))
                        ->successNotificationTitle(__('messages.settings.tags_deleted_successfully')),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTags::route('/'),
        ];
    }
}
