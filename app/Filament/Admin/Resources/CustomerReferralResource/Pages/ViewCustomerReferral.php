<?php

namespace App\Filament\Admin\Resources\CustomerReferralResource\Pages;

use App\Filament\Admin\Resources\CustomerReferralResource;
use Filament\Infolists\Components;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomerReferral extends ViewRecord
{
    protected static string $resource = CustomerReferralResource::class;

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Referral')
                    ->schema([
                        Components\TextEntry::make('referrer.name')
                            ->label('Referrer (Customer)')
                            ->url(fn ($record) => \App\Filament\Admin\Resources\UserResource::getUrl('view', ['record' => $record->referrer_user_id])),
                        Components\TextEntry::make('referredBusiness.business_name')
                            ->label('Referred Business')
                            ->url(fn ($record) => $record->referred_business_id ? \App\Filament\Admin\Resources\BusinessResource::getUrl('view', ['record' => $record->referred_business_id]) : null),
                        Components\TextEntry::make('referral_code')->label('Code')->copyable(),
                        Components\TextEntry::make('status')->badge()->color(fn ($s) => $s === 'qualified' ? 'success' : 'warning'),
                        Components\TextEntry::make('created_at')->label('Referred On')->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }
}
