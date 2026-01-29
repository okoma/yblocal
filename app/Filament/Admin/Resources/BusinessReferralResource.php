<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\BusinessReferralResource\Pages;
use App\Models\BusinessReferral;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BusinessReferralResource extends Resource
{
    protected static ?string $model = BusinessReferral::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationLabel = 'Business Referrals';
    protected static ?string $navigationGroup = 'Referral Program';
    protected static ?int $navigationSort = 4;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('referrerBusiness.business_name')
                    ->label('Referrer Business')
                    ->searchable()
                    ->sortable()
                    ->url(fn (BusinessReferral $record) => BusinessResource::getUrl('view', ['record' => $record->referrer_business_id])),

                Tables\Columns\TextColumn::make('referredBusiness.business_name')
                    ->label('Referred Business')
                    ->searchable()
                    ->sortable()
                    ->url(fn (BusinessReferral $record) => BusinessResource::getUrl('view', ['record' => $record->referred_business_id])),

                Tables\Columns\TextColumn::make('referral_code')
                    ->label('Code')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('referral_credits_awarded')
                    ->label('Credits Awarded')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'credited' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Referred On')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['pending' => 'Pending', 'credited' => 'Credited']),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBusinessReferrals::route('/'),
            'view' => Pages\ViewBusinessReferral::route('/{record}'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['referral_code', 'referrerBusiness.business_name', 'referredBusiness.business_name'];
    }
}
