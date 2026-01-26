<?php

namespace App\Filament\Customer\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    
    protected static string $view = 'filament.customer.pages.dashboard';
    
    public function getWidgets(): array
    {
        return [
            \App\Filament\Customer\Widgets\StatsOverviewWidget::class,
            \App\Filament\Customer\Widgets\RecentActivityWidget::class,
        ];
    }
    
    public function getColumns(): int | array
    {
        return 2;
    }
}
