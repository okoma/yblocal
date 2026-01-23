<?php

namespace App\Filament\Admin\Resources\TransactionResource\Pages;

use App\Filament\Admin\Resources\TransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Illuminate\Support\Str;

class ViewTransaction extends ViewRecord
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Transaction Information')
                    ->schema([
                        Components\TextEntry::make('transaction_ref')
                            ->label('Transaction Reference')
                            ->copyable()
                            ->copyMessage('Reference copied!')
                            ->icon('heroicon-o-clipboard'),

                        Components\TextEntry::make('payment_gateway_ref')
                            ->label('Gateway Reference')
                            ->copyable()
                            ->visible(fn ($record) => $record->payment_gateway_ref),

                        Components\TextEntry::make('user.name')
                            ->label('User')
                            ->url(fn ($record) => $record->user ? route('filament.admin.resources.users.view', $record->user) : null),

                        Components\TextEntry::make('user.email')
                            ->label('User Email')
                            ->copyable(),

                        Components\TextEntry::make('transactionable_type')
                            ->label('Transaction Type')
                            ->formatStateUsing(fn (string $state): string => match($state) {
                                'App\Models\Subscription' => 'Subscription',
                                'App\Models\AdCampaign' => 'Ad Campaign',
                                'App\Models\Wallet' => 'Wallet Funding',
                                default => Str::afterLast($state, '\\'),
                            })
                            ->badge()
                            ->color(fn (string $state): string => match($state) {
                                'App\Models\Subscription' => 'success',
                                'App\Models\AdCampaign' => 'info',
                                'App\Models\Wallet' => 'warning',
                                default => 'gray',
                            }),

                        Components\TextEntry::make('transactionable_id')
                            ->label('Related ID'),

                        Components\TextEntry::make('amount')
                            ->label('Amount')
                            ->money('NGN')
                            ->size('lg')
                            ->weight('bold'),

                        Components\TextEntry::make('currency')
                            ->label('Currency'),

                        Components\TextEntry::make('payment_method')
                            ->label('Payment Method')
                            ->formatStateUsing(fn (string $state): string => Str::title(str_replace('_', ' ', $state)))
                            ->badge()
                            ->color(fn (string $state): string => match($state) {
                                'paystack' => 'success',
                                'flutterwave' => 'warning',
                                'bank_transfer' => 'info',
                                'wallet' => 'gray',
                                default => 'gray',
                            }),

                        Components\TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->size('lg')
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

                Components\Section::make('Timestamps')
                    ->schema([
                        Components\TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime('M j, Y g:i A'),

                        Components\TextEntry::make('paid_at')
                            ->label('Paid At')
                            ->dateTime('M j, Y g:i A')
                            ->visible(fn ($record) => $record->paid_at),

                        Components\TextEntry::make('failed_at')
                            ->label('Failed At')
                            ->dateTime('M j, Y g:i A')
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
                    ->visible(fn ($record) => $record->is_refunded)
                    ->collapsed(),

                Components\Section::make('Metadata')
                    ->schema([
                        Components\KeyValueEntry::make('metadata')
                            ->label('Transaction Metadata')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record->metadata)
                    ->collapsed(),

                Components\Section::make('Gateway Response')
                    ->schema([
                        Components\KeyValueEntry::make('gateway_response')
                            ->label('Gateway Response Data')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record->gateway_response)
                    ->collapsed(),
            ]);
    }
}
