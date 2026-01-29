<?php

namespace App\Filament\Customer\Pages;

use App\Models\QuoteResponse;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class PriceCompare extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    
    protected static ?string $navigationLabel = 'Price Compare';
    
    protected static ?string $navigationGroup = 'Quote';
    
    protected static ?int $navigationSort = 3;
    
    protected static string $view = 'filament.customer.pages.price-compare';
    
    public function getTitle(): string
    {
        return 'Price Comparison';
    }
    
    public function getShortlistedQuotes()
    {
        return QuoteResponse::whereHas('quoteRequest', function ($query) {
            $query->where('user_id', Auth::id());
        })
        ->where('status', 'shortlisted')
        ->with(['business', 'quoteRequest'])
        ->orderBy('price', 'asc')
        ->get();
    }
}
