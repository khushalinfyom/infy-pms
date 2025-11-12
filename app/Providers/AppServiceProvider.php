<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Support\Facades\FilamentView;
use Filament\Tables\View\TablesRenderHook;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        FilamentView::registerRenderHook(
            TablesRenderHook::TOOLBAR_SEARCH_BEFORE,
            fn() => view('filament.tables.search-tooltip', ['allTables' => $this->allTables]),
            scopes: $this->allTables
        );
    }

    public $allTables = [
        \App\Filament\Resources\Departments\Pages\ManageDepartments::class,
        \App\Filament\Resources\Roles\Pages\ListRoles::class,
        \App\Filament\Resources\Clients\Pages\ManageClients::class,
    ];
}
