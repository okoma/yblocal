<?php

namespace App\Filament\Business\Resources\TransactionResource\Pages;

use App\Filament\Business\Resources\TransactionResource;
use App\Models\AdCampaign;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Services\ActiveBusiness;
use Filament\Facades\Filament;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionResource::class;
    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function baseQuery(): Builder
    {
        $id = app(ActiveBusiness::class)->getActiveBusinessId();
        $q = Transaction::query()->where('user_id', Filament::auth()->id());
        if ($id === null) {
            return $q->whereRaw('1 = 0');
        }
        return $q->where(function (Builder $q) use ($id) {
            $q->whereHasMorph('transactionable', [Subscription::class], fn (Builder $m) => $m->where('business_id', $id))
                ->orWhereHasMorph('transactionable', [AdCampaign::class], fn (Builder $m) => $m->where('business_id', $id));
        });
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(fn () => $this->baseQuery()->where('status', 'pending')->count())
                ->badgeColor('warning'),
            'completed' => Tab::make('Completed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'completed'))
                ->badge(fn () => $this->baseQuery()->where('status', 'completed')->count())
                ->badgeColor('success'),
            'subscriptions' => Tab::make('Subscriptions')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('transactionable_type', Subscription::class))
                ->icon('heroicon-o-star'),
            'campaigns' => Tab::make('Ad Campaigns')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('transactionable_type', AdCampaign::class))
                ->icon('heroicon-o-megaphone'),
        ];
    }
}
