<?php

namespace App\Filament\Admin\Resources\CustomerReferralWithdrawalResource\Pages;

use App\Filament\Admin\Resources\CustomerReferralWithdrawalResource;
use Filament\Infolists\Components;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomerReferralWithdrawal extends ViewRecord
{
    protected static string $resource = CustomerReferralWithdrawalResource::class;

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Request')
                    ->schema([
                        Components\TextEntry::make('user.name')
                            ->label('User')
                            ->url(fn ($record) => \App\Filament\Admin\Resources\UserResource::getUrl('view', ['record' => $record->user_id])),
                        Components\TextEntry::make('amount')->label('Amount')->money('NGN')->size('lg'),
                        Components\TextEntry::make('bank_name')->label('Bank'),
                        Components\TextEntry::make('account_name')->label('Account Name'),
                        Components\TextEntry::make('account_number')->label('Account Number'),
                        Components\TextEntry::make('sort_code')->label('Sort Code')->placeholder('—'),
                        Components\TextEntry::make('status')->badge()->color(fn ($s) => match ($s) { 'pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', default => 'gray' }),
                        Components\TextEntry::make('created_at')->label('Requested At')->dateTime(),
                    ])
                    ->columns(2),

                Components\Section::make('Processing')
                    ->schema([
                        Components\TextEntry::make('processor.name')->label('Processed By')->placeholder('—'),
                        Components\TextEntry::make('processed_at')->label('Processed At')->dateTime()->placeholder('—'),
                        Components\TextEntry::make('rejection_reason')->label('Rejection Reason')->placeholder('—')->visible(fn ($record) => $record->status === 'rejected'),
                        Components\TextEntry::make('notes')->label('Notes')->placeholder('—'),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record->processed_at !== null),
            ]);
    }
}
