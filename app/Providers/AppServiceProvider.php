<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Support\Facades\FilamentView;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Http\Responses\Contracts\LogoutResponse;
use Filament\Tables\View\TablesRenderHook;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            LoginResponse::class,
            \App\Http\Responses\LoginResponse::class
        );

        $this->app->singleton(
            LogoutResponse::class,
            \App\Http\Responses\LogoutResponse::class
        );
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
        \App\Filament\Resources\ArchivedUsers\Pages\ManageArchivedUsers::class,
        \App\Filament\Resources\Tasks\Pages\ManageTasks::class,
        \App\Filament\Resources\Users\Pages\ManageUsers::class,
        \App\Filament\Resources\Tags\Pages\ManageTags::class,
        \App\Filament\Resources\ActivityTypes\Pages\ManageActivityTypes::class,
        \App\Filament\Resources\Taxes\Pages\ManageTaxes::class,

    ];
}
