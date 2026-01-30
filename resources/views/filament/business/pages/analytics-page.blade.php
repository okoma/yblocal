<x-filament-panels::page>
    <style>
        .metrics-grid {
            display: grid !important;
            grid-template-columns: repeat(4, 1fr) !important;
            gap: 1rem !important;
        }
        
        @media (max-width: 1023px) {
            .metrics-grid {
                grid-template-columns: repeat(2, 1fr) !important;
            }
        }
        
        @media (max-width: 639px) {
            .metrics-grid {
                grid-template-columns: 1fr !important;
            }
        }
        
        .metrics-grid > div {
            min-width: 0 !important;
            width: 100% !important;
        }
      
    .metrics-scroll-container {
        display: flex;
        overflow-x: auto;
        gap: 1rem;
        padding-bottom: 1rem;
        scroll-behavior: smooth;
        -webkit-overflow-scrolling: touch;
    }
    
    .metrics-scroll-container::-webkit-scrollbar {
        height: 8px;
    }
    
    .metrics-scroll-container::-webkit-scrollbar-track {
        background: transparent;
        border-radius: 10px;
        transition: background 0.3s ease;
    }
    
    .metrics-scroll-container::-webkit-scrollbar-thumb {
        background: transparent;
        border-radius: 10px;
        transition: background 0.3s ease;
    }
    
    .metric-card:hover ~ .metrics-scroll-container::-webkit-scrollbar-track,
    .metrics-scroll-container:has(.metric-card:hover)::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    
    .metric-card:hover ~ .metrics-scroll-container::-webkit-scrollbar-thumb,
    .metrics-scroll-container:has(.metric-card:hover)::-webkit-scrollbar-thumb {
        background: #888;
    }
    
    .metrics-scroll-container::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
    
    .metric-card {
        min-width: 280px;
        flex-shrink: 0;
    }
    
    /* Trend Colors */
    .up-trend {
        background-color: #dcfce7 !important;
        color: #166534 !important;
    }
    
    .dark .up-trend {
        background-color: rgba(34, 197, 94, 0.3) !important;
        color: #86efac !important;
    }
    
    .down-trend {
        background-color: #fee2e2 !important;
        color: #991b1b !important;
    }
    
    .dark .down-trend {
        background-color: rgba(239, 68, 68, 0.3) !important;
        color: #fca5a5 !important;
    }
    
    .neutral-trend {
        background-color: #f3f4f6 !important;
        color: #1f2937 !important;
    }
    
    .dark .neutral-trend {
        background-color: #374151 !important;
        color: #9ca3af !important;
    }
      
    </style>
    
    <div class="space-y-6">
        <!-- Header with Date Range Filter -->
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold tracking-tight">Analytics Dashboard</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Track your business performance and engagement metrics</p>
            </div>
            <div class="flex items-center gap-3">
                <!-- Date Range Selector -->
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="dateRange">
                        <option value="today">Today</option>
                        <option value="yesterday">Yesterday</option>
                        <option value="7">Last 7 days</option>
                        <option value="30">Last 30 days</option>
                        <option value="90">Last 90 days</option>
                        <option value="365">Last year</option>
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
        </div>

        @php
            $views = $this->getViewsData();
            $interactions = $this->getInteractionsData();
            $leads = $this->getLeadsData();
            $clicks = $this->getClicksData();
            $impressions = $this->getImpressionsData();
            $ctr = $this->getCTRData();
            $geographic = $this->getGeographicData();
            $device = $this->getDeviceData();
            $interactionBreakdown = $this->getInteractionBreakdownData();
            $uniqueVisitors = $this->getUniqueVisitorsData();
            
            // Calculate trends with proper handling
            $viewsTrend = 0;
            $viewsTrendDirection = 'neutral';
            if (isset($views['previous_total'])) {
                if ($views['previous_total'] > 0) {
                    $viewsTrend = round((($views['total'] - $views['previous_total']) / $views['previous_total']) * 100, 1);
                    $viewsTrendDirection = $viewsTrend > 0 ? 'up' : ($viewsTrend < 0 ? 'down' : 'neutral');
                } elseif ($views['total'] > 0) {
                    // If previous was 0 but current has data, show as 100% increase
                    $viewsTrend = 100;
                    $viewsTrendDirection = 'up';
                } elseif ($views['previous_total'] == 0 && $views['total'] == 0) {
                    // Both are 0, no change
                    $viewsTrend = 0;
                    $viewsTrendDirection = 'neutral';
                }
            }
            
            $interactionsTrend = 0;
            $interactionsTrendDirection = 'neutral';
            if (isset($interactions['previous_total'])) {
                if ($interactions['previous_total'] > 0) {
                    $interactionsTrend = round((($interactions['total'] - $interactions['previous_total']) / $interactions['previous_total']) * 100, 1);
                    $interactionsTrendDirection = $interactionsTrend > 0 ? 'up' : ($interactionsTrend < 0 ? 'down' : 'neutral');
                } elseif ($interactions['total'] > 0) {
                    $interactionsTrend = 100;
                    $interactionsTrendDirection = 'up';
                } elseif ($interactions['previous_total'] == 0 && $interactions['total'] == 0) {
                    $interactionsTrend = 0;
                    $interactionsTrendDirection = 'neutral';
                }
            }
            
            $leadsTrend = 0;
            $leadsTrendDirection = 'neutral';
            if (isset($leads['previous_total'])) {
                if ($leads['previous_total'] > 0) {
                    $leadsTrend = round((($leads['total'] - $leads['previous_total']) / $leads['previous_total']) * 100, 1);
                    $leadsTrendDirection = $leadsTrend > 0 ? 'up' : ($leadsTrend < 0 ? 'down' : 'neutral');
                } elseif ($leads['total'] > 0) {
                    $leadsTrend = 100;
                    $leadsTrendDirection = 'up';
                } elseif ($leads['previous_total'] == 0 && $leads['total'] == 0) {
                    $leadsTrend = 0;
                    $leadsTrendDirection = 'neutral';
                }
            }
            
            $conversionTrend = 0;
            $conversionTrendDirection = 'neutral';
            if (isset($leads['previous_conversion_rate'])) {
                $conversionTrend = round($leads['conversion_rate'] - $leads['previous_conversion_rate'], 1);
                $conversionTrendDirection = $conversionTrend > 0 ? 'up' : ($conversionTrend < 0 ? 'down' : 'neutral');
            }
            
            // Clicks trend
            $clicksTrend = 0;
            $clicksTrendDirection = 'neutral';
            if (isset($clicks['previous_total'])) {
                if ($clicks['previous_total'] > 0) {
                    $clicksTrend = round((($clicks['total'] - $clicks['previous_total']) / $clicks['previous_total']) * 100, 1);
                    $clicksTrendDirection = $clicksTrend > 0 ? 'up' : ($clicksTrend < 0 ? 'down' : 'neutral');
                } elseif ($clicks['total'] > 0) {
                    $clicksTrend = 100;
                    $clicksTrendDirection = 'up';
                }
            }
            
            // Impressions trend
            $impressionsTrend = 0;
            $impressionsTrendDirection = 'neutral';
            if (isset($impressions['previous_total'])) {
                if ($impressions['previous_total'] > 0) {
                    $impressionsTrend = round((($impressions['total'] - $impressions['previous_total']) / $impressions['previous_total']) * 100, 1);
                    $impressionsTrendDirection = $impressionsTrend > 0 ? 'up' : ($impressionsTrend < 0 ? 'down' : 'neutral');
                } elseif ($impressions['total'] > 0) {
                    $impressionsTrend = 100;
                    $impressionsTrendDirection = 'up';
                }
            }
            
            // CTR trend
            $ctrTrend = 0;
            $ctrTrendDirection = 'neutral';
            if (isset($ctr['previous_total'])) {
                $ctrTrend = round($ctr['total'] - $ctr['previous_total'], 1);
                $ctrTrendDirection = $ctrTrend > 0 ? 'up' : ($ctrTrend < 0 ? 'down' : 'neutral');
            }

            // Unique Visitors trend
            $uniqueVisitorsTrend = 0;
            $uniqueVisitorsTrendDirection = 'neutral';
            if (isset($uniqueVisitors['previous_total'])) {
                if ($uniqueVisitors['previous_total'] > 0) {
                    $uniqueVisitorsTrend = round((($uniqueVisitors['total'] - $uniqueVisitors['previous_total']) / $uniqueVisitors['previous_total']) * 100, 1);
                    $uniqueVisitorsTrendDirection = $uniqueVisitorsTrend > 0 ? 'up' : ($uniqueVisitorsTrend < 0 ? 'down' : 'neutral');
                } elseif ($uniqueVisitors['total'] > 0) {
                    $uniqueVisitorsTrend = 100;
                    $uniqueVisitorsTrendDirection = 'up';
                }
            }
        @endphp
                                                                                               

<!-- All Key Metrics Cards - Horizontal Scroll -->
<div class="metrics-scroll-container">
    <!-- Clicks Card -->
    <div class="metric-card relative overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Clicks</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                    {{ number_format($clicks['total']) }}
                </p>
                <div class="mt-3 flex items-center gap-2">
                    @if($clicksTrendDirection === 'up')
                        <span class="up-trend inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                            {{ abs($clicksTrend) }}%
                        </span>
                    @elseif($clicksTrendDirection === 'down')
                        <span class="down-trend inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            {{ abs($clicksTrend) }}%
                        </span>
                    @else
                        <span class="neutral-trend inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                            </svg>
                            0%
                        </span>
                    @endif
                    <span class="text-xs text-gray-500 dark:text-gray-400">vs previous period</span>
                </div>
            </div>
            <div class="rounded-full bg-cyan-100 dark:bg-cyan-900/20 p-3">
                <svg class="h-6 w-6 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Impressions Card -->
    <div class="metric-card relative overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Impressions</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                    {{ number_format($impressions['total']) }}
                </p>
                <div class="mt-3 flex items-center gap-2">
                    @if($impressionsTrendDirection === 'up')
                        <span class="up-trend inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                            {{ abs($impressionsTrend) }}%
                        </span>
                    @elseif($impressionsTrendDirection === 'down')
                        <span class="down-trend inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            {{ abs($impressionsTrend) }}%
                        </span>
                    @else
                        <span class="neutral-trend inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                            </svg>
                            0%
                        </span>
                    @endif
                    <span class="text-xs text-gray-500 dark:text-gray-400">vs previous period</span>
                </div>
            </div>
            <div class="rounded-full bg-indigo-100 dark:bg-indigo-900/20 p-3">
                <svg class="h-6 w-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Total Views Card -->
    <div class="metric-card relative overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Views</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                    {{ number_format($views['total']) }}
                </p>
                <div class="mt-3 flex items-center gap-2">
                    @if($viewsTrendDirection === 'up')
                        <span class="up-trend inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                            {{ abs($viewsTrend) }}%
                        </span>
                    @elseif($viewsTrendDirection === 'down')
                        <span class="down-trend inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            {{ abs($viewsTrend) }}%
                        </span>
                    @else
                        <span class="neutral-trend inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                            </svg>
                            0%
                        </span>
                    @endif
                    <span class="text-xs text-gray-500 dark:text-gray-400">vs previous period</span>
                </div>
            </div>
            <div class="rounded-full bg-blue-100 dark:bg-blue-900/20 p-3">
                <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Total Interactions Card -->
    <div class="metric-card relative overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Interactions</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                    {{ number_format($interactions['total']) }}
                </p>
                <div class="mt-3 flex items-center gap-2">
                    @if($interactionsTrendDirection === 'up')
                        <span class="up-trend inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                            {{ abs($interactionsTrend) }}%
                        </span>
                    @elseif($interactionsTrendDirection === 'down')
                        <span class="down-trend inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            {{ abs($interactionsTrend) }}%
                        </span>
                    @else
                        <span class="neutral-trend inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                            </svg>
                            0%
                        </span>
                    @endif
                    <span class="text-xs text-gray-500 dark:text-gray-400">vs previous period</span>
                </div>
            </div>
            <div class="rounded-full bg-green-100 dark:bg-green-900/20 p-3">
                <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Total Leads Card -->
    <div class="metric-card relative overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Leads</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                    {{ number_format($leads['total']) }}
                </p>
                <div class="mt-3 flex items-center gap-2">
                    @if($leadsTrendDirection === 'up')
                        <span class="up-trend inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                            {{ abs($leadsTrend) }}%
                        </span>
                    @elseif($leadsTrendDirection === 'down')
                        <span class="down-trend inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            {{ abs($leadsTrend) }}%
                        </span>
                    @else
                        <span class="neutral-trend inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                            </svg>
                            0%
                        </span>
                    @endif
                    <span class="text-xs text-gray-500 dark:text-gray-400">vs previous period</span>
                </div>
            </div>
            <div class="rounded-full bg-purple-100 dark:bg-purple-900/20 p-3">
                <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Conversion Rate Card -->
    <div class="metric-card relative overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Conversion Rate</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                    {{ $leads['conversion_rate'] }}%
                </p>
                <div class="mt-3 flex items-center gap-2">
                    @if($conversionTrendDirection === 'up')
                        <span class="up-trend inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                            {{ abs($conversionTrend) }}%
                        </span>
                    @elseif($conversionTrendDirection === 'down')
                        <span class="down-trend inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            {{ abs($conversionTrend) }}%
                        </span>
                    @else
                        <span class="neutral-trend inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                            </svg>
                            0%
                        </span>
                    @endif
                    <span class="text-xs text-gray-500 dark:text-gray-400">vs previous period</span>
                </div>
            </div>
            <div class="rounded-full bg-orange-100 dark:bg-orange-900/20 p-3">
                <svg class="h-6 w-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- CTR Card -->
    <div class="metric-card relative overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">CTR</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                    {{ $ctr['total'] }}%
                </p>
                <div class="mt-3 flex items-center gap-2">
                    @if($ctrTrendDirection === 'up')
                        <span class="up-trend inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                            {{ abs($ctrTrend) }}%
                        </span>
                    @elseif($ctrTrendDirection === 'down')
                        <span class="down-trend inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            {{ abs($ctrTrend) }}%
                        </span>
                    @else
                        <span class="neutral-trend inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                            </svg>
                            0%
                        </span>
                    @endif
                    <span class="text-xs text-gray-500 dark:text-gray-400">vs previous period</span>
                </div>
            </div>
            <div class="rounded-full bg-pink-100 dark:bg-pink-900/20 p-3">
                <svg class="h-6 w-6 text-pink-600 dark:text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Unique Visitors Card -->
    <div class="metric-card relative overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Unique Visitors</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                    {{ number_format($uniqueVisitors['total']) }}
                </p>
                <div class="mt-3 flex items-center gap-2">
                    @if($uniqueVisitorsTrendDirection === 'up')
                        <span class="up-trend inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                            {{ abs($uniqueVisitorsTrend) }}%
                        </span>
                    @elseif($uniqueVisitorsTrendDirection === 'down')
                        <span class="down-trend inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            {{ abs($uniqueVisitorsTrend) }}%
                        </span>
                    @else
                        <span class="neutral-trend inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                            </svg>
                            0%
                        </span>
                    @endif
                    <span class="text-xs text-gray-500 dark:text-gray-400">vs previous period</span>
                </div>
            </div>
            <div class="rounded-full bg-teal-100 dark:bg-teal-900/20 p-3">
                <svg class="h-6 w-6 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </div>
        </div>
    </div>
</div>      
      
      <!-- Views by Source -->
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span class="text-base font-semibold">Views by Source</span>
                </div>
            </x-slot>
            
            @if(empty($views['by_source']))
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <p class="mt-4 text-sm font-medium text-gray-900 dark:text-white">No views data available</p>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Views will appear here once your business starts receiving traffic.</p>
                </div>
            @else
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($views['by_source'] as $source => $count)
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-4 transition hover:shadow-md">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ ucfirst($source) }}</p>
                                    <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($count) }}</p>
                                </div>
                                <div class="ml-3 flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-white dark:bg-gray-700">
                                    <span class="text-lg font-semibold text-primary-600">
                                        {{ $views['total'] > 0 ? round($count / $views['total'] * 100) : 0 }}%
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>

        <!-- Customer Interactions Breakdown -->
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    <span class="text-base font-semibold">Customer Interactions</span>
                </div>
            </x-slot>
            
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                <div class="group rounded-lg border border-gray-200 dark:border-gray-700 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/10 dark:to-blue-800/10 p-4 transition hover:shadow-lg">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-500 text-white text-xl">📞</div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Phone Calls</p>
                            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($interactions['calls']) }}</p>
                        </div>
                    </div>
                </div>

                <div class="group rounded-lg border border-gray-200 dark:border-gray-700 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/10 dark:to-green-800/10 p-4 transition hover:shadow-lg">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-green-500 text-white text-xl">💬</div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">WhatsApp</p>
                            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($interactions['whatsapp']) }}</p>
                        </div>
                    </div>
                </div>

                <div class="group rounded-lg border border-gray-200 dark:border-gray-700 bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/10 dark:to-purple-800/10 p-4 transition hover:shadow-lg">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-purple-500 text-white text-xl">📧</div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Emails</p>
                            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($interactions['emails']) }}</p>
                        </div>
                    </div>
                </div>

                <div class="group rounded-lg border border-gray-200 dark:border-gray-700 bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/10 dark:to-orange-800/10 p-4 transition hover:shadow-lg">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-orange-500 text-white text-xl">🌐</div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Website</p>
                            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($interactions['website_clicks']) }}</p>
                        </div>
                    </div>
                </div>

                <div class="group rounded-lg border border-gray-200 dark:border-gray-700 bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/10 dark:to-red-800/10 p-4 transition hover:shadow-lg">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-500 text-white text-xl">🗺️</div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Map Clicks</p>
                            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($interactions['map_clicks']) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </x-filament::section>

        <!-- Geographic Distribution -->
        <div class="grid gap-6 lg:grid-cols-2">
            <!-- Views by Country -->
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-base font-semibold">Top Countries by Views</span>
                    </div>
                </x-slot>
                
                @if(empty($geographic['views_by_country']))
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-6 text-center">
                        <p class="text-sm text-gray-500 dark:text-gray-400">No geographic data available yet</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($geographic['views_by_country'] as $country => $count)
                            <div class="flex items-center justify-between rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3">
                                <span class="font-medium text-gray-900 dark:text-white">{{ $country }}</span>
                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ number_format($count) }} views</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-filament::section>

            <!-- Views by City -->
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span class="text-base font-semibold">Top Cities by Views</span>
                    </div>
                </x-slot>
                
                @if(empty($geographic['views_by_city']))
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-6 text-center">
                        <p class="text-sm text-gray-500 dark:text-gray-400">No city data available yet</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($geographic['views_by_city'] as $city => $count)
                            <div class="flex items-center justify-between rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3">
                                <span class="font-medium text-gray-900 dark:text-white">{{ $city }}</span>
                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ number_format($count) }} views</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-filament::section>
        </div>

        <!-- Device Distribution -->
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    <span class="text-base font-semibold">Traffic by Device Type</span>
                </div>
            </x-slot>
            
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Views by Device -->
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/10 dark:to-blue-800/10 p-4">
                    <h4 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Views</h4>
                    <div class="space-y-2">
                        @foreach($device['views_by_device'] as $deviceType => $count)
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">{{ ucfirst($deviceType) }}</span>
                                <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($count) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Impressions by Device -->
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gradient-to-br from-indigo-50 to-indigo-100 dark:from-indigo-900/10 dark:to-indigo-800/10 p-4">
                    <h4 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Impressions</h4>
                    <div class="space-y-2">
                        @foreach($device['impressions_by_device'] as $deviceType => $count)
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">{{ ucfirst($deviceType) }}</span>
                                <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($count) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Clicks by Device -->
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gradient-to-br from-cyan-50 to-cyan-100 dark:from-cyan-900/10 dark:to-cyan-800/10 p-4">
                    <h4 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Clicks</h4>
                    <div class="space-y-2">
                        @foreach($device['clicks_by_device'] as $deviceType => $count)
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">{{ ucfirst($deviceType) }}</span>
                                <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($count) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Interactions by Device -->
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/10 dark:to-green-800/10 p-4">
                    <h4 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Interactions</h4>
                    <div class="space-y-2">
                        @foreach($device['interactions_by_device'] as $deviceType => $count)
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">{{ ucfirst($deviceType) }}</span>
                                <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($count) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </x-filament::section>

        <!-- Interaction Sources -->
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    </svg>
                    <span class="text-base font-semibold">Interactions by Traffic Source</span>
                </div>
            </x-slot>
            
            @if(empty($interactionBreakdown['by_source']))
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-8 text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">No interaction source data available yet</p>
                </div>
            @else
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach($interactionBreakdown['by_source'] as $source => $count)
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-4 transition hover:shadow-md">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ ucfirst($source) }}</p>
                            <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($count) }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>

    </div>
</x-filament-panels::page>