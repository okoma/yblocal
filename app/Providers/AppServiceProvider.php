<?php

namespace App\Providers;

use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\ServiceProvider;

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
        $this->registerFilamentPanelAssets();
    }

    /**
     * Register panel-specific CSS/JS via FilamentAsset (cache-friendly, use filament:assets).
     */
    protected function registerFilamentPanelAssets(): void
    {
        $base = __DIR__ . '/../../resources';

        FilamentAsset::register([
            Css::make('filament-panels-admin', "{$base}/css/filament-panels/admin.css"),
            Js::make('filament-panels-admin', "{$base}/js/filament-panels/admin.js")->defer(),
            Css::make('filament-panels-business', "{$base}/css/filament-panels/business.css"),
            Js::make('filament-panels-business', "{$base}/js/filament-panels/business.js")->defer(),
            Css::make('filament-panels-customer', "{$base}/css/filament-panels/customer.css"),
            Js::make('filament-panels-customer', "{$base}/js/filament-panels/customer.js")->defer(),
        ]);
    }
}
