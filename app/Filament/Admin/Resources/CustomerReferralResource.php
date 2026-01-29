<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CustomerReferralResource\Pages;
use App\Models\CustomerReferral;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomerReferralResource extends Resource
{
    protected static ?string $model = CustomerReferral::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    protected static ?string $navigationLabel = 'Customer Referrals';
    protected static ?string $navigationGroup = 'Referral Program';
    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('referrer.name')
                    ->label('Referrer (Customer)')
                    ->searchable()
                    ->sortable()
                    ->url(fn (CustomerReferral $record) => \App\Filament\Admin\Resources\UserResource::getUrl('view', ['record' => $record->referrer_user_id])),

                Tables\Columns\TextColumn::make('referredBusiness.business_name')
                    ->label('Referred Business')
                    ->searchable()
                    ->sortable()
                    ->url(fn (CustomerReferral $record) => $record->referred_business_id ? \App\Filament\Admin\Resources\BusinessResource::getUrl('view', ['record' => $record->referred_business_id]) : null),

                Tables\Columns\TextColumn::make('referral_code')
                    ->label('Code')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'qualified' => 'success',
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
                    ->options(['pending' => 'Pending', 'qualified' => 'Qualified']),
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
            'index' => Pages\ListCustomerReferrals::route('/'),
            'view' => Pages\ViewCustomerReferral::route('/{record}'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['referral_code', 'referrer.name', 'referredBusiness.business_name'];
    }
}
