<?php
// ============================================
// app/Filament/Business/Widgets/StatsOverviewWidget.php
// Main dashboard statistics for business owners
// ============================================

namespace App\Filament\Business\Widgets;

use App\Models\Business;
use App\Models\Lead;
use App\Models\Review;
use App\Models\BusinessView;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        
        // Get all user's businesses
        $businesses = $user->businesses()->get();
        
        // Aggregate statistics
        $totalBusinesses = $businesses->count();
        
        // Get all business IDs for queries
        $businessIds = $businesses->pluck('id');
        
        // Total Leads
        $totalLeads = Lead::whereIn('business_id', $businessIds)->count();
        
        $newLeads = Lead::whereIn('business_id', $businessIds)
            ->where('status', 'new')->count();
        
        // Total Reviews
        $totalReviews = Review::where('reviewable_type', 'App\Models\Business')
            ->whereIn('reviewable_id', $businessIds)
            ->count();
        
        $avgRating = Review::where('reviewable_type', 'App\Models\Business')
            ->whereIn('reviewable_id', $businessIds)
            ->avg('rating') ?? 0;
        
        // Total Views
        $totalViews = BusinessView::whereIn('business_id', $businessIds)->count();
        
        $viewsThisMonth = BusinessView::whereIn('business_id', $businessIds)
            ->whereMonth('view_date', now()->month)
            ->whereYear('view_date', now()->year)
            ->count();
        
        return [
            Stat::make('Total Businesses', $totalBusinesses)
                ->description('Active businesses')
                ->descriptionIcon('heroicon-o-building-storefront')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),
            
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
                ->color('info')
                ->chart([20, 25, 30, 28, 35, 40, 45]),
        ];
    }
}