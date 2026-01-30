<?php
// ============================================
// app/Filament/Admin/Resources/UserResource/Pages/ListUsers.php
// ============================================

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use App\Models\User;
use App\Services\ExportService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\UserRole;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

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
                        'users-all-' . now()->format('Ymd-His') . '.csv',
                        [
                            'ID',
                            'Name',
                            'Email',
                            'Phone',
                            'Role',
                            'Active',
                            'Banned',
                            'Created At',
                        ],
                        User::query(),
                        fn (User $record) => [
                            $record->id,
                            $record->name,
                            $record->email,
                            $record->phone,
                            $record->role?->value ?? $record->role,
                            $record->is_active ? 'yes' : 'no',
                            $record->is_banned ? 'yes' : 'no',
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
            'all' => Tab::make()
                ->badge(fn () => $this->getModel()::count()),
            
            'admins' => Tab::make()
                ->badge(fn () => $this->getModel()::where('role', UserRole::ADMIN)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('role', UserRole::ADMIN)),
            
            'business_owners' => Tab::make('Business Owners')
                ->badge(fn () => $this->getModel()::where('role', UserRole::BUSINESS_OWNER)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('role', UserRole::BUSINESS_OWNER)),
            
            'customers' => Tab::make()
                ->badge(fn () => $this->getModel()::where('role', UserRole::CUSTOMER)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('role', UserRole::CUSTOMER)),
            
            'banned' => Tab::make()
                ->badge(fn () => $this->getModel()::where('is_banned', true)->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_banned', true)),
        ];
    }
}