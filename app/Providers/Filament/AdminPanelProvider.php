<?php

namespace App\Providers\Filament;

use App\Filament\Pages\CustomEditProfile;
use App\Filament\Pages\CustomLogin;
use App\Filament\Pages\CustomRequestPasswordReset;
use App\Filament\Pages\CustomResetPassword;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
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
        $stopWatchColor = 'text-primary-600 dark:text-primary-400';

        return Blade::render("
        <button
            @click=\"\$dispatch('open-modal', { id: 'stop-watch-modal' })\"
            type='button'
            wire:loading.attr='disabled'
            class='fi-ac-grouped-action rounded-full transition-colors duration-200'
            style='cursor: pointer; display: inline-flex; align-items: center; justify-content: center;'
        >
            @svg('phosphor-alarm-fill', ['class' => \"$stopWatchColor w-6 h-6 mr-2\"])
        </button>

        <a href='{{ \$settingsUrl }}'
            class='fi-ac-grouped-action rounded-full transition-colors duration-200'
            wire:loading.attr='disabled'>
            @svg('phosphor-gear-fine-fill', ['class' => 'text-gray-600 dark:text-gray-300 w-6 h-6 mr-2'])
        </a>
    ", [
            'settingsUrl' => $settingsUrl,
        'stopWatchColor' => $stopWatchColor,
        ]);
    }
}
