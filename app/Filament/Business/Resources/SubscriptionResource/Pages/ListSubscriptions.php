<?php

namespace App\Filament\Business\Resources\SubscriptionResource\Pages;

use App\Filament\Business\Resources\SubscriptionResource;
use App\Services\ActiveBusiness;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListSubscriptions extends ListRecords
{
    protected static string $resource = SubscriptionResource::class;
    
    public function mount(): void
    {
        $active = app(ActiveBusiness::class);
        $businessId = $active->getActiveBusinessId();
        
        if ($businessId === null) {
            // No active business, redirect to subscription plans page
            $this->redirect(route('filament.business.pages.subscription-page'));
            return;
        }
        
        // Get active subscription for current business
        $subscription = static::getResource()::getModel()::where('user_id', auth()->id())
            ->where('business_id', $businessId)
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->first();
        
        // If active subscription exists, redirect to view page
        if ($subscription) {
            $this->redirect(static::getResource()::getUrl('view', ['record' => $subscription->id]));
            return;
        }
        
        // Otherwise, redirect to subscription plans page
        $this->redirect(route('filament.business.pages.subscription-page'));
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('browse_plans')
                ->label('Browse Plans')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->url(fn () => route('filament.business.pages.subscription-page'))
                ->visible(function () {
                    // Show if user has no active subscription
                    return !auth()->user()->hasActiveSubscription();
                }),

            Actions\Action::make('upgrade')
                ->label('Upgrade Plan')
                ->icon('heroicon-o-arrow-up-circle')
                ->color('success')
                ->url(fn () => route('filament.business.pages.subscription-page'))
                ->visible(function () {
                    // Show if user has active subscription
                    return auth()->user()->hasActiveSubscription();
                }),
        ];
    }

    protected function baseQuery(): Builder
    {
        $id = app(ActiveBusiness::class)->getActiveBusinessId();
        $q = $this->getModel()::where('user_id', auth()->id());
        if ($id === null) {
            return $q->whereIn('business_id', []);
        }
        return $q->where('business_id', $id);
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(fn () => $this->baseQuery()->count()),
            'active' => Tab::make('Active')
                ->badge(fn () => $this->baseQuery()->where('status', 'active')->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'active')),
            'trialing' => Tab::make('Trial')
                ->badge(fn () => $this->baseQuery()->where('status', 'trialing')->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'trialing')),
            'expiring_soon' => Tab::make('Expiring Soon')
                ->badge(fn () => $this->baseQuery()->where('status', 'active')->whereBetween('ends_at', [now(), now()->addDays(7)])->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'active')->whereBetween('ends_at', [now(), now()->addDays(7)])),
            'past_due' => Tab::make('Past Due')
                ->badge(fn () => $this->baseQuery()->where('status', 'past_due')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'past_due')),
            'cancelled' => Tab::make('Cancelled')
                ->badge(fn () => $this->baseQuery()->whereIn('status', ['cancelled', 'expired'])->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', ['cancelled', 'expired'])),
            'paused' => Tab::make('Paused')
                ->badge(fn () => $this->baseQuery()->where('status', 'paused')->count())
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'paused')),
        ];
    }
}