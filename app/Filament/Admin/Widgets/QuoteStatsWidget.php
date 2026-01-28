<?php
// ============================================
// app/Filament/Admin/Widgets/QuoteStatsWidget.php
// Location: app/Filament/Admin/Widgets/QuoteStatsWidget.php
// Purpose: Display quote system statistics
// ============================================

namespace App\Filament\Admin\Widgets;

use App\Models\QuoteRequest;
use App\Models\QuoteResponse;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class QuoteStatsWidget extends BaseWidget
{
    protected static ?int $sort = 5;
    
    protected function getStats(): array
    {
        $openRequests = QuoteRequest::where('status', 'open')->count();
        $totalRequests = QuoteRequest::count();
        $totalResponses = QuoteResponse::count();
        $acceptedQuotes = QuoteResponse::where('status', 'accepted')->count();
        
        // Today's stats
        $requestsToday = QuoteRequest::whereDate('created_at', today())->count();
        $responsesToday = QuoteResponse::whereDate('created_at', today())->count();
        
        // This month's stats
        $requestsThisMonth = QuoteRequest::whereDate('created_at', '>=', now()->startOfMonth())->count();
        $responsesThisMonth = QuoteResponse::whereDate('created_at', '>=', now()->startOfMonth())->count();
        
        // Average responses per request
        $avgResponses = $totalRequests > 0 ? round($totalResponses / $totalRequests, 1) : 0;
        
        // Acceptance rate
        $acceptanceRate = $totalResponses > 0 ? round(($acceptedQuotes / $totalResponses) * 100, 1) : 0;
        
        return [
            Stat::make('Total Quote Requests', number_format($totalRequests))
                ->description("{$openRequests} open requests")
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info')
                ->icon('heroicon-o-document-text')
                ->url(\App\Filament\Admin\Resources\QuoteRequestResource::getUrl('index')),
            
            Stat::make('Total Quote Responses', number_format($totalResponses))
                ->description("{$responsesThisMonth} this month")
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->icon('heroicon-o-document-check')
                ->url(\App\Filament\Admin\Resources\QuoteResponseResource::getUrl('index')),
            
            Stat::make('Accepted Quotes', number_format($acceptedQuotes))
                ->description("{$acceptanceRate}% acceptance rate")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->icon('heroicon-o-check-circle'),
            
            Stat::make('Avg Responses/Request', number_format($avgResponses, 1))
                ->description("{$requestsToday} requests today")
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('primary')
                ->icon('heroicon-o-chart-bar'),
        ];
    }
}
