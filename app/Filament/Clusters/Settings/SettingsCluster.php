<?php

namespace App\Filament\Clusters\Settings;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class SettingsCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog;

    public static function getNavigationGroup(): ?string
    {
        return __('messages.settings.settings');
    }

    public static function canAccess(): bool
    {
        return authUserHasPermission('manage_settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.settings.settings');
    }
}
