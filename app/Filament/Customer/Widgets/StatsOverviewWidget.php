<?php

namespace App\Filament\Customer\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        
        return [
            Stat::make('Saved Businesses', $user->savedBusinesses()->count())
                ->description('Businesses you saved')
                ->descriptionIcon('heroicon-o-heart')
                ->color('danger')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3])
                ->url('/customer/saved-businesses'),
            
            Stat::make('My Reviews', $user->reviews()->count())
                ->description('Reviews you\'ve written')
                ->descriptionIcon('heroicon-o-star')
                ->color('warning')
                ->chart([3, 5, 6, 8, 4, 6, 5, 7])
                ->url('/customer/my-reviews'),
            
            Stat::make('My Inquiries', $user->leads()->count())
                ->description('Businesses contacted')
                ->descriptionIcon('heroicon-o-chat-bubble-left-right')
                ->color('info')
                ->chart([2, 4, 3, 5, 4, 6, 4, 5])
                ->url('/customer/my-inquiries'),
        ];
    }
}
