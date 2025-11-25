<?php

namespace App\Filament\Resources\Calenders;

use App\Enums\AdminPanelSidebar;
use App\Filament\Resources\Calenders\Pages\ManageCalenders;
use App\Filament\Resources\Calenders\Widgets\CalendarWidget;
use App\Models\Calender;
use App\Models\TimeEntry;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CalenderResource extends Resource
{
    protected static ?string $model = TimeEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static ?int $navigationSort = AdminPanelSidebar::CALENDAR->value;

    protected static ?string $recordTitleAttribute = 'TimeEntry';

    public static function getNavigationLabel(): string
    {
        return 'Calender';
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
