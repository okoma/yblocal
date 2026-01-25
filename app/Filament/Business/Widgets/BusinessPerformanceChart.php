<?php

namespace App\Filament\Business\Widgets;

use App\Models\BusinessView;
use App\Models\Lead;
use App\Services\ActiveBusiness;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class BusinessPerformanceChart extends ChartWidget
{
    protected static ?string $heading = 'Performance Overview (Last 30 Days)';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';
    protected function getData(): array
    {
        $id = app(ActiveBusiness::class)->getActiveBusinessId();
        $dates = collect();
        for ($i = 29; $i >= 0; $i--) {
            $dates->push(now()->subDays($i)->format('Y-m-d'));
        }

        $viewsByDate = collect();
        $leadsByDate = collect();
        if ($id !== null) {
            $viewsByDate = BusinessView::where('business_id', $id)
                ->where('view_date', '>=', now()->subDays(30))
                ->select('view_date', DB::raw('count(*) as total'))
                ->groupBy('view_date')
                ->pluck('total', 'view_date');
            $leadsByDate = Lead::where('business_id', $id)
                ->where('created_at', '>=', now()->subDays(30))
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
                ->groupBy('date')
                ->pluck('total', 'date');
        }
        
        // Map to dates
        $viewsData = $dates->map(fn($date) => $viewsByDate->get($date, 0))->toArray();
        $leadsData = $dates->map(fn($date) => $leadsByDate->get($date, 0))->toArray();
        
        return [
            'datasets' => [
                [
                    'label' => 'Views',
                    'data' => $viewsData,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Leads',
                    'data' => $leadsData,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'tension' => 0.3,
                ],
            ],
            'labels' => $dates->map(fn($date) => \Carbon\Carbon::parse($date)->format('M d'))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}