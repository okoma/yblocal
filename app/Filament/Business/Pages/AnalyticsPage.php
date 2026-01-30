<?php

namespace App\Filament\Business\Pages;

use App\Services\ActiveBusiness;
use App\Models\BusinessView;
use App\Models\BusinessInteraction;
use App\Models\BusinessImpression;
use App\Models\BusinessClick;
use App\Models\Lead;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class AnalyticsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Analytics';

    protected static ?string $navigationGroup = null;

    protected static ?int $navigationSort = 6;

    protected static string $view = 'filament.business.pages.analytics-page';

    public $dateRange = '30';

    public function getTitle(): string
    {
        return 'Analytics & Reports';
    }

    public function getHeading(): string
    {
        return '';
    }

    protected function getFilteredBusinessIds(): \Illuminate\Support\Collection
    {
        $id = app(ActiveBusiness::class)->getActiveBusinessId();
        return $id === null ? collect() : collect([$id]);
    }
    
    /**
     * Get date range based on selection
     */
    protected function getDateRanges()
    {
        return match($this->dateRange) {
            'today' => [
                'current_start' => now()->startOfDay(),
                'current_end' => now()->endOfDay(),
                'previous_start' => now()->subDay()->startOfDay(),
                'previous_end' => now()->subDay()->endOfDay(),
            ],
            'yesterday' => [
                'current_start' => now()->subDay()->startOfDay(),
                'current_end' => now()->subDay()->endOfDay(),
                'previous_start' => now()->subDays(2)->startOfDay(),
                'previous_end' => now()->subDays(2)->endOfDay(),
            ],
            default => [
                'current_start' => now()->subDays((int)$this->dateRange)->startOfDay(),
                'current_end' => now()->endOfDay(),
                'previous_start' => now()->subDays((int)$this->dateRange * 2)->startOfDay(),
                'previous_end' => now()->subDays((int)$this->dateRange)->endOfDay(),
            ]
        };
    }
    
    /**
     * Get Views Data with Previous Period Comparison
     */
    public function getViewsData()
    {
        $businessIds = $this->getFilteredBusinessIds();
        $dates = $this->getDateRanges();
        
        // Current Period Total Views
        $totalViews = BusinessView::whereIn('business_id', $businessIds)
            ->whereBetween('view_date', [$dates['current_start'], $dates['current_end']])
            ->count();
        
        // Previous Period Total Views
        $previousTotalViews = BusinessView::whereIn('business_id', $businessIds)
            ->whereBetween('view_date', [$dates['previous_start'], $dates['previous_end']])
            ->count();
        
        // Views by Source (Current Period) - FIXED: Handle enum properly
        $viewsBySource = BusinessView::whereIn('business_id', $businessIds)
            ->whereBetween('view_date', [$dates['current_start'], $dates['current_end']])
            ->select('referral_source', DB::raw('count(*) as total'))
            ->groupBy('referral_source')
            ->get()
            ->mapWithKeys(fn($item) => [$item->referral_source->value => $item->total])
            ->toArray();
        
        // Views by Date (Current Period)
        $viewsByDate = BusinessView::whereIn('business_id', $businessIds)
            ->whereBetween('view_date', [$dates['current_start'], $dates['current_end']])
            ->select('view_date', DB::raw('count(*) as total'))
            ->groupBy('view_date')
            ->orderBy('view_date')
            ->get();
        
        return [
            'total' => $totalViews,
            'previous_total' => $previousTotalViews,
            'by_source' => $viewsBySource,
            'by_date' => $viewsByDate,
        ];
    }
    
    /**
     * Get Interactions Data with Previous Period Comparison
     */
    public function getInteractionsData()
    {
        $businessIds = $this->getFilteredBusinessIds();
        $dates = $this->getDateRanges();
        
        // Current Period Interactions
        $currentInteractions = BusinessInteraction::whereIn('business_id', $businessIds)
            ->whereBetween('interaction_date', [$dates['current_start'], $dates['current_end']])
            ->select('interaction_type', DB::raw('count(*) as total'))
            ->groupBy('interaction_type')
            ->get()
            ->pluck('total', 'interaction_type');
        
        // Previous Period Interactions
        $previousInteractions = BusinessInteraction::whereIn('business_id', $businessIds)
            ->whereBetween('interaction_date', [$dates['previous_start'], $dates['previous_end']])
            ->select('interaction_type', DB::raw('count(*) as total'))
            ->groupBy('interaction_type')
            ->get()
            ->pluck('total', 'interaction_type');
        
        return [
            'total' => $currentInteractions->sum(),
            'previous_total' => $previousInteractions->sum(),
            'calls' => $currentInteractions['call'] ?? 0,
            'whatsapp' => $currentInteractions['whatsapp'] ?? 0,
            'emails' => $currentInteractions['email'] ?? 0,
            'website_clicks' => $currentInteractions['website'] ?? 0,
            'map_clicks' => $currentInteractions['map'] ?? 0,
        ];
    }
    
    /**
     * Get Leads Data with Previous Period Comparison
     */
    public function getLeadsData()
    {
        $businessIds = $this->getFilteredBusinessIds();
        $dates = $this->getDateRanges();
        
        // Current Period Leads
        $totalCurrentLeads = Lead::whereIn('business_id', $businessIds)
            ->whereBetween('created_at', [$dates['current_start'], $dates['current_end']])
            ->count();
        
        // Previous Period Leads
        $totalPreviousLeads = Lead::whereIn('business_id', $businessIds)
            ->whereBetween('created_at', [$dates['previous_start'], $dates['previous_end']])
            ->count();
        
        // Current Period Leads by Status
        $currentLeadsByStatus = Lead::whereIn('business_id', $businessIds)
            ->whereBetween('created_at', [$dates['current_start'], $dates['current_end']])
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status');
        
        // Previous Period Leads by Status
        $previousLeadsByStatus = Lead::whereIn('business_id', $businessIds)
            ->whereBetween('created_at', [$dates['previous_start'], $dates['previous_end']])
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status');
        
        // Calculate Current Conversion Rate
        $currentViews = BusinessView::whereIn('business_id', $businessIds)
            ->whereBetween('view_date', [$dates['current_start'], $dates['current_end']])
            ->count();
        
        $currentConversionRate = $currentViews > 0 
            ? round(($totalCurrentLeads / $currentViews) * 100, 1) 
            : 0;
        
        // Calculate Previous Conversion Rate
        $previousViews = BusinessView::whereIn('business_id', $businessIds)
            ->whereBetween('view_date', [$dates['previous_start'], $dates['previous_end']])
            ->count();
        
        $previousConversionRate = $previousViews > 0 
            ? round(($totalPreviousLeads / $previousViews) * 100, 1) 
            : 0;
        
        return [
            'total' => $totalCurrentLeads,
            'previous_total' => $totalPreviousLeads,
            'by_status' => $currentLeadsByStatus,
            'conversion_rate' => $currentConversionRate,
            'previous_conversion_rate' => $previousConversionRate,
        ];
    }
    
    /**
     * Get Impressions Data with Previous Period Comparison
     * Impressions = when business listings are visible on archive/category/search pages
     */
    public function getImpressionsData()
    {
        $businessIds = $this->getFilteredBusinessIds();
        $dates = $this->getDateRanges();
        
        // Current Period Total Impressions
        $totalImpressions = BusinessImpression::whereIn('business_id', $businessIds)
            ->whereBetween('impression_date', [$dates['current_start'], $dates['current_end']])
            ->count();
        
        // Previous Period Total Impressions
        $previousTotalImpressions = BusinessImpression::whereIn('business_id', $businessIds)
            ->whereBetween('impression_date', [$dates['previous_start'], $dates['previous_end']])
            ->count();
        
        // Impressions by Source (Current Period) - FIXED: Handle enum properly
        $impressionsBySource = BusinessImpression::whereIn('business_id', $businessIds)
            ->whereBetween('impression_date', [$dates['current_start'], $dates['current_end']])
            ->select('referral_source', DB::raw('count(*) as total'))
            ->groupBy('referral_source')
            ->get()
            ->mapWithKeys(fn($item) => [$item->referral_source->value => $item->total])
            ->toArray();
        
        // Impressions by Page Type (Current Period) - FIXED: Handle enum properly
        $impressionsByPageType = BusinessImpression::whereIn('business_id', $businessIds)
            ->whereBetween('impression_date', [$dates['current_start'], $dates['current_end']])
            ->select('page_type', DB::raw('count(*) as total'))
            ->groupBy('page_type')
            ->get()
            ->mapWithKeys(fn($item) => [$item->page_type->value => $item->total])
            ->toArray();
        
        return [
            'total' => $totalImpressions,
            'previous_total' => $previousTotalImpressions,
            'by_source' => $impressionsBySource,
            'by_page_type' => $impressionsByPageType,
        ];
    }
    
    /**
     * Get Clicks Data with Previous Period Comparison
     * Clicks = when someone visits business detail page (cookie-based, one per person)
     */
    public function getClicksData()
    {
        $businessIds = $this->getFilteredBusinessIds();
        $dates = $this->getDateRanges();
        
        // Current Period Total Clicks
        $totalClicks = BusinessClick::whereIn('business_id', $businessIds)
            ->whereBetween('click_date', [$dates['current_start'], $dates['current_end']])
            ->count();
        
        // Previous Period Total Clicks
        $previousTotalClicks = BusinessClick::whereIn('business_id', $businessIds)
            ->whereBetween('click_date', [$dates['previous_start'], $dates['previous_end']])
            ->count();
        
        // Clicks by Source (Current Period) - FIXED: Handle enum properly
        $clicksBySource = BusinessClick::whereIn('business_id', $businessIds)
            ->whereBetween('click_date', [$dates['current_start'], $dates['current_end']])
            ->select('referral_source', DB::raw('count(*) as total'))
            ->groupBy('referral_source')
            ->get()
            ->mapWithKeys(fn($item) => [$item->referral_source->value => $item->total])
            ->toArray();
        
        // Clicks by Page Type (Current Period) - FIXED: Handle enum properly
        $clicksByPageType = BusinessClick::whereIn('business_id', $businessIds)
            ->whereBetween('click_date', [$dates['current_start'], $dates['current_end']])
            ->select('source_page_type', DB::raw('count(*) as total'))
            ->groupBy('source_page_type')
            ->get()
            ->mapWithKeys(fn($item) => [$item->source_page_type->value => $item->total])
            ->toArray();
        
        return [
            'total' => $totalClicks,
            'previous_total' => $previousTotalClicks,
            'by_source' => $clicksBySource,
            'by_page_type' => $clicksByPageType,
        ];
    }
    
    /**
     * Get CTR (Click-Through Rate) Data with Previous Period Comparison
     * CTR = (Clicks / Impressions) × 100
     */
    public function getCTRData()
    {
        $clicks = $this->getClicksData();
        $impressions = $this->getImpressionsData();
        
        // Current CTR
        $currentCTR = $impressions['total'] > 0 
            ? round(($clicks['total'] / $impressions['total']) * 100, 1) 
            : 0;
        
        // Previous CTR
        $previousCTR = $impressions['previous_total'] > 0 
            ? round(($clicks['previous_total'] / $impressions['previous_total']) * 100, 1) 
            : 0;
        
        return [
            'total' => $currentCTR,
            'previous_total' => $previousCTR,
        ];
    }
    
    public function updatedDateRange(): void
    {
        // Data auto-refreshes via Livewire reactivity
    }
}