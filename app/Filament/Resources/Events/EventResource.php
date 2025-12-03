<?php

namespace App\Filament\Resources\Events;

use App\Enums\AdminPanelSidebar;
use App\Filament\Resources\Events\Pages\ManageEvents;
use App\Filament\Resources\Events\Widgets\EventWidget;
use App\Models\Event;
use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?int $navigationSort = AdminPanelSidebar::EVENTS->value;

    protected static ?string $recordTitleAttribute = 'Event';

    public static function canViewAny(): bool
    {
        return authUserHasPermission('manage_events');
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.users.events');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('added_by')
                    ->default(auth()->user()->id),

                TextInput::make('title')
                    ->label(__('messages.projects.title'))
                    ->placeholder(__('messages.projects.title'))
                    ->required()
                    ->columnSpanFull(),

                DateTimePicker::make('start_date')
                    ->label(__('messages.settings.start_date'))
                    ->placeholder(__('messages.settings.start_date'))
                    ->native(false)
                    ->required()
                    ->live()
                    ->maxDate(fn(callable $get) => $get('end_date')),

                DateTimePicker::make('end_date')
                    ->label(__('messages.settings.end_date'))
                    ->placeholder(__('messages.settings.end_date'))
                    ->native(false)
                    ->required()
                    ->live()
                    ->minDate(fn(callable $get) => $get('start_date')),

                Select::make('type')
                    ->label(__('messages.users.type'))
                    ->options(Event::EVENTS)
                    ->native(false)
                    ->required(),

                RichEditor::make('description')
                    ->label(__('messages.common.description'))
                    ->placeholder(__('messages.common.description'))
                    ->columnSpanFull()
                    ->extraAttributes(['style' => 'height: 200px;'])
                    ->toolbarButtons([
                        ['bold', 'italic', 'underline', 'strike', 'subscript', 'superscript', 'link'],
                        ['h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd'],
                        ['blockquote', 'codeBlock', 'bulletList', 'orderedList'],
                        ['undo', 'redo'],
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageEvents::route('/'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            EventWidget::class,
        ];
    }
}
