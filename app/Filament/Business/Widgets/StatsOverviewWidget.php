<?php

namespace App\Filament\Business\Widgets;

use App\Models\Lead;
use App\Models\Review;
use App\Models\BusinessView;
use App\Services\ActiveBusiness;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected $listeners = ['business-switched' => '$refresh'];

    protected function getStats(): array
    {
        $active = app(ActiveBusiness::class);
        $id = $active->getActiveBusinessId();
        $business = $active->getActiveBusiness();

        if ($id === null) {
            return [
                Stat::make('Active Business', '—')
                    ->description('Select a business')
                    ->descriptionIcon('heroicon-o-building-storefront')
                    ->color('gray'),
            ];
        }

        $totalLeads = Lead::where('business_id', $id)->count();
        $newLeads = Lead::where('business_id', $id)->where('status', 'new')->count();
        $totalReviews = Review::where('reviewable_type', 'App\Models\Business')
            ->where('reviewable_id', $id)->count();
        $avgRating = Review::where('reviewable_type', 'App\Models\Business')
            ->where('reviewable_id', $id)->avg('rating') ?? 0;
        $totalViews = BusinessView::where('business_id', $id)->count();
        $viewsThisMonth = BusinessView::where('business_id', $id)
            ->whereMonth('view_date', now()->month)
            ->whereYear('view_date', now()->year)
            ->count();

        return [
            Stat::make('Active Business', $business->business_name ?? '—')
                ->description('Current context')
                ->descriptionIcon('heroicon-o-building-storefront')
                ->color('success'),
            Stat::make('Total Leads', $totalLeads)
                ->description($newLeads > 0 ? "{$newLeads} new leads!" : 'No new leads')
                ->descriptionIcon('heroicon-o-user-plus')
                ->color($newLeads > 0 ? 'warning' : 'gray')
                ->url(route('filament.business.resources.leads.index')),
            Stat::make('Average Rating', number_format($avgRating, 1) . ' ⭐')
                ->description("{$totalReviews} total reviews")
                ->descriptionIcon('heroicon-o-star')
                ->color($avgRating >= 4 ? 'success' : ($avgRating >= 3 ? 'warning' : 'danger')),
            Stat::make('Total Views', number_format($totalViews))
                ->description("{$viewsThisMonth} this month")
                ->descriptionIcon('heroicon-o-eye')
                ->color('info'),
        ];
    }
}