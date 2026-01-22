<?php

namespace App\Filament\Business\Resources\TransactionResource\Pages;

use App\Filament\Business\Resources\TransactionResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Illuminate\Support\Str;

class ViewTransaction extends ViewRecord
{
    protected static string $resource = TransactionResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Transaction Details')
                    ->schema([
                        Components\TextEntry::make('transaction_ref')
                            ->label('Transaction Reference')
                            ->copyable()
                            ->copyMessage('Reference copied!')
                            ->icon('heroicon-o-clipboard')
                            ->size('lg')
                            ->weight('bold'),

                        Components\TextEntry::make('payment_gateway_ref')
                            ->label('Gateway Reference')
                            ->copyable()
                            ->visible(fn ($record) => $record->payment_gateway_ref),

                        Components\TextEntry::make('transactionable_type')
                            ->label('Transaction Type')
                            ->formatStateUsing(fn (string $state): string => match($state) {
                                'App\Models\Subscription' => 'Subscription Payment',
                                'App\Models\AdCampaign' => 'Ad Campaign Payment',
                                'App\Models\Wallet' => 'Wallet Funding',
                                default => Str::afterLast($state, '\\'),
                            })
                            ->badge()
                            ->size('lg')
                            ->color(fn (string $state): string => match($state) {
                                'App\Models\Subscription' => 'success',
                                'App\Models\AdCampaign' => 'info',
                                'App\Models\Wallet' => 'warning',
                                default => 'gray',
                            }),

                        Components\TextEntry::make('amount')
                            ->label('Amount Paid')
                            ->money('NGN')
                            ->size('xl')
                            ->weight('bold')
                            ->color('success'),

                        Components\TextEntry::make('payment_method')
                            ->label('Payment Method')
                            ->formatStateUsing(fn (string $state): string => Str::title(str_replace('_', ' ', $state)))
                            ->badge()
                            ->size('lg')
                            ->color(fn (string $state): string => match($state) {
                                'paystack' => 'success',
                                'flutterwave' => 'warning',
                                'bank_transfer' => 'info',
                                'wallet' => 'gray',
                                default => 'gray',
                            }),

                        Components\TextEntry::make('status')
                            ->label('Payment Status')
                            ->badge()
                            ->size('xl')
                            ->color(fn (string $state): string => match($state) {
                                'completed' => 'success',
                                'pending' => 'warning',
                                'failed' => 'danger',
                                'refunded' => 'info',
                                default => 'gray',
                            }),

                        Components\TextEntry::make('description')
                            ->label('Description')
                            ->columnSpanFull()
                            ->visible(fn ($record) => $record->description),
                    ])
                    ->columns(2),

                Components\Section::make('Payment Timeline')
                    ->schema([
                        Components\TextEntry::make('created_at')
                            ->label('Transaction Initiated')
                            ->dateTime('M j, Y g:i A')
                            ->icon('heroicon-o-clock'),

                        Components\TextEntry::make('paid_at')
                            ->label('Payment Completed')
                            ->dateTime('M j, Y g:i A')
                            ->icon('heroicon-o-check-circle')
                            ->color('success')
                            ->visible(fn ($record) => $record->paid_at),

                        Components\TextEntry::make('failed_at')
                            ->label('Payment Failed')
                            ->dateTime('M j, Y g:i A')
                            ->icon('heroicon-o-x-circle')
                            ->color('danger')
                            ->visible(fn ($record) => $record->failed_at),
                    ])
                    ->columns(3)
                    ->collapsed(),

                Components\Section::make('Refund Information')
                    ->schema([
                        Components\IconEntry::make('is_refunded')
                            ->label('Refunded')
                            ->boolean()
                            ->size('lg'),

                        Components\TextEntry::make('refund_amount')
                            ->label('Refund Amount')
                            ->money('NGN')
                            ->size('lg')
                            ->weight('bold')
                            ->visible(fn ($record) => $record->is_refunded),

                        Components\TextEntry::make('refund_reason')
                            ->label('Refund Reason')
                            ->columnSpanFull()
                            ->visible(fn ($record) => $record->refund_reason),

                        Components\TextEntry::make('refunded_at')
                            ->label('Refunded At')
                            ->dateTime('M j, Y g:i A')
                            ->visible(fn ($record) => $record->refunded_at),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record->is_refunded),

                Components\Section::make('Transaction Metadata')
                    ->schema([
                        Components\KeyValueEntry::make('metadata')
                            ->label('Additional Information')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record->metadata && count($record->metadata) > 0)
                    ->collapsed(),
            ]);
    }
}
