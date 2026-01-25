<?php

namespace App\Filament\Business\Pages;

use App\Services\ActiveBusiness;
use App\Models\BusinessView;
use App\Models\BusinessInteraction;
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

    protected $listeners = ['business-switched' => '$refresh'];

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
                'current_start' => now()->subDays((int)$this->dateRange),
                'current_end' => now(),
                'previous_start' => now()->subDays((int)$this->dateRange * 2),
                'previous_end' => now()->subDays((int)$this->dateRange),
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
        
        // Views by Source (Current Period)
        $viewsBySource = BusinessView::whereIn('business_id', $businessIds)
            ->whereBetween('view_date', [$dates['current_start'], $dates['current_end']])
            ->select('referral_source', DB::raw('count(*) as total'))
            ->groupBy('referral_source')
            ->get()
            ->pluck('total', 'referral_source')
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
    
    public function updatedDateRange(): void
    {
        // Data auto-refreshes via Livewire reactivity
    }
}