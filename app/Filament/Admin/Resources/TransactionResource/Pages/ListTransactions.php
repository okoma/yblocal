<?php

namespace App\Filament\Admin\Resources\TransactionResource\Pages;

use App\Filament\Admin\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\ExportService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionResource::class;

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
                        'transactions-all-' . now()->format('Ymd-His') . '.csv',
                        [
                            'Transaction Ref',
                            'User',
                            'User Email',
                            'Business',
                            'Amount',
                            'Currency',
                            'Payment Method',
                            'Status',
                            'Created At',
                            'Paid At',
                        ],
                        Transaction::query()->with(['user', 'business']),
                        fn (Transaction $record) => [
                            $record->transaction_ref,
                            $record->user?->name,
                            $record->user?->email,
                            $record->business?->business_name,
                            $record->amount,
                            $record->currency,
                            $record->payment_method,
                            $record->status,
                            optional($record->created_at)->toDateTimeString(),
                            optional($record->paid_at)->toDateTimeString(),
                        ]
                    );
                }),
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Transactions'),
            
            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(fn () => \App\Models\Transaction::where('status', 'pending')->count())
                ->badgeColor('warning'),
            
            'completed' => Tab::make('Completed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'completed'))
                ->badge(fn () => \App\Models\Transaction::where('status', 'completed')->count())
                ->badgeColor('success'),
            
            'failed' => Tab::make('Failed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'failed'))
                ->badge(fn () => \App\Models\Transaction::where('status', 'failed')->count())
                ->badgeColor('danger'),
            
            'refunded' => Tab::make('Refunded')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_refunded', true))
                ->badge(fn () => \App\Models\Transaction::where('is_refunded', true)->count())
                ->badgeColor('info'),
        ];
    }
}
