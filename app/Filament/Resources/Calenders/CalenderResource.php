<?php

namespace App\Filament\Resources\Calenders;

use App\Enums\AdminPanelSidebar;
use App\Filament\Resources\Calenders\Pages\ManageCalenders;
use App\Filament\Resources\Calenders\Widgets\CalendarWidget;
use App\Models\TimeEntry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CalenderResource extends Resource
{
    protected static ?string $model = TimeEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static ?int $navigationSort = AdminPanelSidebar::CALENDAR->value;

    protected static ?string $recordTitleAttribute = 'TimeEntry';

    public static function getNavigationLabel(): string
    {
        return __('messages.users.calender');
    }

    public static function canViewAny(): bool
    {
        return authUserHasPermission('manage_calendar_view');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCalenders::route('/'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            CalendarWidget::class,
        ];
    }
}
