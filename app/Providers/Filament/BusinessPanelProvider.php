<?php

namespace App\Providers\Filament;

use App\Http\Middleware\AllowUnverifiedBusinessCreation;
use App\Http\Middleware\EnsureActiveBusiness;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentAsset;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class BusinessPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('business')
            ->path('dashboard')
            ->domain('biz.yellowbooks.ng')
            ->login(\App\Filament\Business\Pages\Auth\Login::class)
            ->registration(\App\Filament\Business\Pages\Auth\Register::class)
            ->passwordReset(\App\Filament\Business\Pages\Auth\RequestPasswordReset::class)
            ->emailVerification(\App\Filament\Business\Pages\Auth\EmailVerificationPrompt::class)
            ->brandName('YellowBooks')
            ->brandLogo(asset('images/logo.png'))
            ->brandLogoHeight('2rem')
            ->favicon(asset('images/favicon.png'))
            ->colors([
                'primary' => Color::Blue,
            ])
            ->collapsibleNavigationGroups(false)
            //->sidebarCollapsibleOnDesktop()
            ->font('Inter', url: asset('fonts/filament/filament/inter/index.css'))
            ->discoverResources(in: app_path('Filament/Business/Resources'), for: 'App\\Filament\\Business\\Resources')
            ->discoverPages(in: app_path('Filament/Business/Pages'), for: 'App\\Filament\\Business\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Business/Widgets'), for: 'App\\Filament\\Business\\Widgets')
            ->widgets([
                //Widgets\AccountWidget::class,
            ])
            ->renderHook(PanelsRenderHook::SIDEBAR_NAV_START, fn () => view('filament.components.business-switcher-sidebar'))
            ->renderHook(PanelsRenderHook::CONTENT_END, fn () => view('filament.components.global-footer'))
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
                AllowUnverifiedBusinessCreation::class, // Allow unverified users to create business
                EnsureActiveBusiness::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s') // Temporarily disabled to test SPA
            ->spa();
    }

    /** Bump this (e.g. v1 → v2) with each push to bust cache for business panel CSS/JS. */
    private const BUSINESS_ASSET_VERSION = 'v16';

    public function boot(): void
    {
        $v = '?v=' . self::BUSINESS_ASSET_VERSION;
        FilamentAsset::register([
            Css::make('business-panel-styles', asset('css/filament-panels/business.css') . $v),
            Js::make('business-panel-js', asset('js/filament-panels/business.js') . $v),
        ], 'business');
    }
}