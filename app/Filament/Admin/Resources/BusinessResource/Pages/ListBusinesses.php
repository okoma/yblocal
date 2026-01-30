<?php
// ============================================
// app/Filament/Admin/Resources/BusinessResource/Pages/ListBusinesses.php
// ============================================
namespace App\Filament\Admin\Resources\BusinessResource\Pages;

use App\Filament\Admin\Resources\BusinessResource;
use App\Models\Business;
use App\Services\ExportService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListBusinesses extends ListRecords
{
    protected static string $resource = BusinessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_all')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->visible(fn () => auth()->user()?->can('export-data'))
                ->action(function () {
                    return ExportService::streamCsvFromQuery(
                        'businesses-all-' . now()->format('Ymd-His') . '.csv',
                        [
                            'ID',
                            'Name',
                            'Owner',
                            'Owner Email',
                            'Type',
                            'Status',
                            'Verified',
                            'Premium',
                            'State',
                            'City',
                            'Created At',
                        ],
                        Business::query()->with(['owner', 'businessType']),
                        fn (Business $record) => [
                            $record->id,
                            $record->business_name,
                            $record->owner?->name,
                            $record->owner?->email,
                            $record->businessType?->name,
                            $record->status,
                            $record->is_verified ? 'yes' : 'no',
                            $record->is_premium ? 'yes' : 'no',
                            $record->state,
                            $record->city,
                            optional($record->created_at)->toDateTimeString(),
                        ]
                    );
                }),
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(fn () => $this->getModel()::count()),
            
            'active' => Tab::make('Active')
                ->badge(fn () => $this->getModel()::where('status', 'active')->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'active')),
            
            'pending' => Tab::make('Pending Review')
                ->badge(fn () => $this->getModel()::where('status', 'pending_review')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending_review')),
            
            'claimed' => Tab::make('Claimed')
                ->badge(fn () => $this->getModel()::where('is_claimed', true)->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_claimed', true)),
            
            'verified' => Tab::make('Verified')
                ->badge(fn () => $this->getModel()::where('is_verified', true)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_verified', true)),
            
            'premium' => Tab::make('Premium')
                ->badge(fn () => $this->getModel()::where('is_premium', true)
                    ->where('premium_until', '>', now())->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_premium', true)
                    ->where('premium_until', '>', now())),
            
            'suspended' => Tab::make('Suspended')
                ->badge(fn () => $this->getModel()::where('status', 'suspended')->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'suspended')),
        ];
    }
}
