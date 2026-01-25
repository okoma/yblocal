<?php
// ============================================
// app/Filament/Business/Resources/ProductResource/Pages/ListProducts.php
// List all products with tabs and filters
// ============================================

namespace App\Filament\Business\Resources\ProductResource\Pages;

use App\Filament\Business\Resources\ProductResource;
use App\Services\ActiveBusiness;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected $listeners = ['business-switched' => '$refresh'];

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Add New Product')
                ->icon('heroicon-o-plus'),
        ];
    }
    
    public function getTabs(): array
    {
        $baseQuery = function () {
            $id = app(ActiveBusiness::class)->getActiveBusinessId();
            $q = $this->getModel()::query();
            if ($id === null) {
                return $q->whereIn('business_id', []);
            }
            return $q->where('business_id', $id);
        };
        return [
            'all' => Tab::make('All Products')
                ->badge(fn () => $baseQuery()->count()),
            'available' => Tab::make('Available')
                ->badge(fn () => $baseQuery()->where('is_available', true)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_available', true)),
            'unavailable' => Tab::make('Unavailable')
                ->badge(fn () => $baseQuery()->where('is_available', false)->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_available', false)),
            'discounted' => Tab::make('On Discount')
                ->badge(fn () => $baseQuery()->where('discount_type', '!=', 'none')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('discount_type', '!=', 'none')),
        ];
    }
}