<?php

namespace App\Filament\Pages;

use App\Enums\AdminPanelSidebar;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class ActivityLogs extends Page
{
    protected string $view = 'filament.pages.activity-logs';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static ?int $navigationSort = AdminPanelSidebar::ACTIVITY_LOGS->value;

    public static function getNavigationLabel(): string
    {
        return 'Activity Logs';
    }

    public function getTitle(): string | Htmlable
    {
        return 'Activity Logs';
    }
}
