<?php

namespace App\Providers\Filament;

use App\Filament\Pages\CustomEditProfile;
use App\Filament\Pages\CustomLogin;
use App\Filament\Pages\CustomRequestPasswordReset;
use App\Filament\Pages\CustomResetPassword;
use App\Models\TimeEntry;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('/')
            ->login(CustomLogin::class)
            ->colors([
                'primary' => Color::Amber,
            ])
            ->passwordReset($requestAction = CustomRequestPasswordReset::class, $resetAction = CustomResetPassword::class)
            ->breadcrumbs(false)
            ->globalSearch(false)
            ->maxContentWidth('full')
            ->sidebarWidth('17rem')
            ->profile(CustomEditProfile::class, isSimple: false)
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->renderHook(PanelsRenderHook::BODY_END, fn() => Blade::render('@livewire(\'change-password-modal\')'))
            ->renderHook('panels::user-menu.profile.after', fn() => $this->changePassword())
            ->renderHook(PanelsRenderHook::SIDEBAR_NAV_START, fn() => view('filament.sidebar.search-in-sidebar'))
            ->renderHook(PanelsRenderHook::GLOBAL_SEARCH_AFTER, fn() => Blade::render('@livewire(\'notification-read\')'))
            ->renderHook(PanelsRenderHook::GLOBAL_SEARCH_BEFORE, fn() => $this->settingsIcon())
            ->renderHook(PanelsRenderHook::GLOBAL_SEARCH_BEFORE, fn() => Blade::render('@livewire(\'stop-watch-modal\')'))
            ->renderHook(PanelsRenderHook::GLOBAL_SEARCH_BEFORE, fn() => $this->stopwatch())
            ->pages([
                Dashboard::class,
            ])
            ->navigationGroups([
                'Sales',
                'Settings',
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                FilamentApexChartsPlugin::make(),
                FilamentFullCalendarPlugin::make()
                    ->editable()
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->viteTheme('resources/css/filament/admin/theme.css');
    }

    public function changePassword(): string
    {
        return '<button @click="$dispatch(\'open-modal\', {id: \'change-password-modal\'})" class="fi-dropdown-list-item fi-ac-grouped-action" type="button" wire:loading.attr="disabled"><svg class="fi-icon fi-size-md" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z"></path></svg><span class="fi-dropdown-list-item-label">' . 'Change Password' . '</span></button>';
    }

    public function settingsIcon(): string
    {
        $settingsUrl = route('filament.admin.settings.pages.general');
        return '<a href="' . $settingsUrl . '" class="fi-ac-grouped-action rounded-full transition-colors duration-200" type="button" wire:loading.attr="disabled"><svg class="fi-icon fi-size-md text-primary-600 dark:text-primary-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg></a>';
    }

    public function stopwatch(): string
    {
        $active = TimeEntry::where('user_id', auth()->id())
            ->whereNull('end_time')
            ->exists();

        $colorClass = $active
            ? 'text-green-600 dark:text-green-400'
            : 'text-red-600 dark:text-red-400';
        return "
        <button
            @click=\"\$dispatch('open-modal', { id: 'stop-watch-modal' })\"
            type=\"button\"
            wire:loading.attr=\"disabled\"
            style=\"cursor: pointer; display: inline-flex; align-items: center; justify-content: center;\"
            class=\"fi-ac-grouped-action rounded-full transition-colors duration-200\"
        >
            <svg
                xmlns=\"http://www.w3.org/2000/svg\"
                width=\"20\"
                height=\"20\"
                viewBox=\"0 0 256 256\"
                class=\"$colorClass\"
                style=\"margin-left: -20px;\"
            >
                <path fill=\"currentColor\" d=\"M128,40a96,96,0,1,0,96,96A96.11,96.11,0,0,0,128,40Zm0,176a80,80,0,1,1,80-80A80.09,80.09,0,0,1,128,216ZM173.66,90.34a8,8,0,0,1,0,11.32l-40,40a8,8,0,0,1-11.32-11.32l40-40A8,8,0,0,1,173.66,90.34ZM96,16a8,8,0,0,1,8-8h48a8,8,0,0,1,0,16H104A8,8,0,0,1,96,16Z\"></path>
            </svg>
        </button>
    ";
    }
}
