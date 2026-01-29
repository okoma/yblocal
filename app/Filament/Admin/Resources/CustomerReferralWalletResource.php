<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CustomerReferralWalletResource\Pages;
use App\Models\CustomerReferralWallet;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CustomerReferralWalletResource extends Resource
{
    protected static ?string $model = CustomerReferralWallet::class;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';
    protected static ?string $navigationLabel = 'Customer Referral Wallets';
    protected static ?string $navigationGroup = 'Referral Program';
    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->url(fn (CustomerReferralWallet $record) => \App\Filament\Admin\Resources\UserResource::getUrl('view', ['record' => $record->user_id])),

                Tables\Columns\TextColumn::make('balance')
                    ->label('Balance')
                    ->money('NGN')
                    ->sortable(),

                Tables\Columns\TextColumn::make('currency')
                    ->label('Currency')
                    ->badge(),

                Tables\Columns\TextColumn::make('transactions_count')
                    ->label('Transactions')
                    ->counts('transactions')
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([])
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
            'index' => Pages\ListCustomerReferralWallets::route('/'),
            'view' => Pages\ViewCustomerReferralWallet::route('/{record}'),
        ];
    }
}
