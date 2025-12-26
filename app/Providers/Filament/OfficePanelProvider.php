<?php

namespace App\Providers\Filament;

use Filament\Panel;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\PanelProvider;
use Filament\Pages\Dashboard;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Filament\Http\Middleware\Authenticate;
use Resma\FilamentAwinTheme\FilamentAwinTheme;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Filament\Http\Middleware\AuthenticateSession;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;

class OfficePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('office')
            ->path('office')
            ->sidebarCollapsibleOnDesktop(true)
            ->globalSearch(true)
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->login()
            ->topbar(true)
            // ->font('Plus Jakarta Sans')
            ->font('Poppins')
            ->favicon('https://res.cloudinary.com/dbr6xazzh/image/upload/v1763576028/01_Logo_Main_f2t5wp.avif')
            ->brandLogo('https://res.cloudinary.com/dbr6xazzh/image/upload/v1763576028/01_Logo_Main_f2t5wp.avif')
            ->brandLogoHeight('5rem')
            ->brandName('Beyond Viral')
            ->colors([
                'primary' => '#48009f',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                // Dashboard is auto-discovered from app/Filament/Pages
            ])
            ->navigationGroups([
                'Campign Area ',
                'Media Planning',
                'Finance',
                'Human Capital ',
                'Database',
                'Master Data',
                'Settings',
            ])
            ->plugins([
                FilamentShieldPlugin::make()
                    ->navigationGroup('Settings'),
                BreezyCore::make()
                    ->myProfile(
                        shouldRegisterUserMenu: true, // Sets the 'account' link in the panel User Menu (default = true)
                        shouldRegisterNavigation: false, // Adds a main navigation item for the My Profile page (default = false)
                        hasAvatars: true, // Enables the avatar upload form component (default = false)
                        slug: 'my-profile' // Sets the slug for the profile page (default = 'my-profile')
                    )
                    ->enableTwoFactorAuthentication(
                        force: false // Force all users to enable 2FA (default = false)
                    )
                    ->enableBrowserSessions(), // Enable browser session management
                FilamentAwinTheme::make(),
                FilamentApexChartsPlugin::make(),
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                // AccountWidget::class,
                // FilamentInfoWidget::class,
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
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
