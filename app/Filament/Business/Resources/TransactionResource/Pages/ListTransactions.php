<?php

namespace App\Filament\Business\Resources\TransactionResource\Pages;

use App\Filament\Business\Resources\TransactionResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Filament\Facades\Filament;

class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action - transactions are created automatically
        ];
    }

    public function getTabs(): array
    {
        $userId = Filament::auth()->id();

        return [
            'all' => Tab::make('All'),
            
            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(fn () => \App\Models\Transaction::where('user_id', $userId)->where('status', 'pending')->count())
                ->badgeColor('warning'),
            
            'completed' => Tab::make('Completed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'completed'))
                ->badge(fn () => \App\Models\Transaction::where('user_id', $userId)->where('status', 'completed')->count())
                ->badgeColor('success'),
            
            'subscriptions' => Tab::make('Subscriptions')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('transactionable_type', 'App\Models\Subscription'))
                ->icon('heroicon-o-star'),
            
            'campaigns' => Tab::make('Ad Campaigns')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('transactionable_type', 'App\Models\AdCampaign'))
                ->icon('heroicon-o-megaphone'),
            
            'wallet' => Tab::make('Wallet')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('transactionable_type', 'App\Models\Wallet'))
                ->icon('heroicon-o-wallet'),
        ];
    }
}
