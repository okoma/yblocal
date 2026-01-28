<?php
// ============================================
// LIST REVIEWS PAGE
// app/Filament/Business/Resources/ReviewResource/Pages/ListReviews.php
// ============================================

namespace App\Filament\Business\Resources\ReviewResource\Pages;

use App\Filament\Business\Resources\ReviewResource;
use App\Services\ActiveBusiness;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListReviews extends ListRecords
{
    protected static string $resource = ReviewResource::class;
    public function getTabs(): array
    {
        $baseQuery = function () {
            $id = app(ActiveBusiness::class)->getActiveBusinessId();
            $q = $this->getModel()::where('reviewable_type', 'App\Models\Business');
            if ($id === null) {
                return $q->whereIn('reviewable_id', []);
            }
            return $q->where('reviewable_id', $id);
        };
        return [
            'all' => Tab::make('All Reviews')
                ->badge(fn () => $baseQuery()->count()),
            'unreplied' => Tab::make('Unreplied')
                ->badge(fn () => $baseQuery()->whereNull('reply')->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('reply')),
            '5_stars' => Tab::make('5 Stars')
                ->badge(fn () => $baseQuery()->where('rating', 5)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('rating', 5)),
            '4_stars' => Tab::make('4 Stars')
                ->badge(fn () => $baseQuery()->where('rating', 4)->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('rating', 4)),
            '3_stars' => Tab::make('3 Stars')
                ->badge(fn () => $baseQuery()->where('rating', 3)->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('rating', 3)),
            'low_ratings' => Tab::make('1-2 Stars')
                ->badge(fn () => $baseQuery()->whereIn('rating', [1, 2])->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('rating', [1, 2])),
            'verified' => Tab::make('Verified Purchase')
                ->badge(fn () => $baseQuery()->where('is_verified_purchase', true)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_verified_purchase', true)),
        ];
    }
}
