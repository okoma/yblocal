<?php

namespace App\Filament\Admin\Resources\BusinessReferralResource\Pages;

use App\Filament\Admin\Resources\BusinessReferralResource;
use Filament\Infolists\Components;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewBusinessReferral extends ViewRecord
{
    protected static string $resource = BusinessReferralResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Referral')
                    ->schema([
                        Components\TextEntry::make('referrerBusiness.business_name')
                            ->label('Referrer Business')
                            ->url(fn ($record) => \App\Filament\Admin\Resources\BusinessResource::getUrl('view', ['record' => $record->referrer_business_id])),
                        Components\TextEntry::make('referredBusiness.business_name')
                            ->label('Referred Business')
                            ->url(fn ($record) => \App\Filament\Admin\Resources\BusinessResource::getUrl('view', ['record' => $record->referred_business_id])),
                        Components\TextEntry::make('referral_code')->label('Code')->copyable(),
                        Components\TextEntry::make('referral_credits_awarded')->label('Credits Awarded'),
                        Components\TextEntry::make('status')->badge()->color(fn ($s) => $s === 'credited' ? 'success' : 'warning'),
                        Components\TextEntry::make('created_at')->label('Referred On')->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }
}