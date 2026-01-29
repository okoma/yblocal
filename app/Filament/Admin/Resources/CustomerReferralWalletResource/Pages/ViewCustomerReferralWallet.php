<?php

namespace App\Filament\Admin\Resources\CustomerReferralWalletResource\Pages;

use App\Filament\Admin\Resources\CustomerReferralWalletResource;
use Filament\Infolists\Components;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomerReferralWallet extends ViewRecord
{
    protected static string $resource = CustomerReferralWalletResource::class;

  public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Wallet')
                    ->schema([
                        Components\TextEntry::make('user.name')
                            ->label('User')
                            ->url(fn ($record) => \App\Filament\Admin\Resources\UserResource::getUrl('view', ['record' => $record->user_id])),
                        Components\TextEntry::make('balance')->label('Balance')->money('NGN')->size('lg'),
                        Components\TextEntry::make('currency')->badge(),
                        Components\TextEntry::make('updated_at')->label('Last Updated')->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }
}
