<?php

namespace App\Filament\Business\Resources\AdCampaignResource\Pages;

use App\Filament\Business\Resources\AdCampaignResource;
use App\Filament\Business\Resources\AdPackageResource;
use App\Services\ActiveBusiness;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListAdCampaigns extends ListRecords
{
    protected static string $resource = AdCampaignResource::class;

    protected $listeners = ['business-switched' => '$refresh'];

    protected function baseQuery(): Builder
    {
        $id = app(ActiveBusiness::class)->getActiveBusinessId();
        $q = $this->getModel()::where('purchased_by', auth()->id());
        if ($id === null) {
            return $q->whereIn('business_id', []);
        }
        return $q->where('business_id', $id);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('browse_packages')
                ->label('Create Campaign')
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->url(fn () => AdPackageResource::getUrl('index')),

            Actions\Action::make('wallet')
                ->label('My Wallet')
                ->icon('heroicon-o-wallet')
                ->color('success')
                ->url(fn () => route('filament.business.pages.wallet-page'))
                ->badge(function () {
                    $wallet = auth()->user()->wallet;
                    return $wallet ? '₦' . number_format($wallet->balance, 0) : null;
                })
                ->badgeColor('success'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(fn () => $this->baseQuery()->count()),
            'active' => Tab::make('Active')
                ->badge(fn () => $this->baseQuery()->where('is_active', true)->where('starts_at', '<=', now())->where('ends_at', '>=', now())->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true)->where('starts_at', '<=', now())->where('ends_at', '>=', now())),
            'scheduled' => Tab::make('Scheduled')
                ->badge(fn () => $this->baseQuery()->where('starts_at', '>', now())->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('starts_at', '>', now())),
            'expiring_soon' => Tab::make('Expiring Soon')
                ->badge(fn () => $this->baseQuery()->where('is_active', true)->whereBetween('ends_at', [now(), now()->addDays(3)])->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true)->whereBetween('ends_at', [now(), now()->addDays(3)])),
            'paused' => Tab::make('Paused')
                ->badge(fn () => $this->baseQuery()->where('is_active', false)->where('ends_at', '>=', now())->count())
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false)->where('ends_at', '>=', now())),
            'expired' => Tab::make('Expired')
                ->badge(fn () => $this->baseQuery()->where('ends_at', '<', now())->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('ends_at', '<', now())),
            'bump_up' => Tab::make('Bump Up')
                ->badge(fn () => $this->baseQuery()->where('type', 'bump_up')->where('is_active', true)->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'bump_up')),
            'sponsored' => Tab::make('Sponsored')
                ->badge(fn () => $this->baseQuery()->where('type', 'sponsored')->where('is_active', true)->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'sponsored')),
            'featured' => Tab::make('Featured')
                ->badge(fn () => $this->baseQuery()->where('type', 'featured')->where('is_active', true)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'featured')),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // You can add campaign statistics widgets here
        ];
    }
}