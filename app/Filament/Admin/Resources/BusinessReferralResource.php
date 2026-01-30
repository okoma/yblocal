<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\BusinessReferralResource\Pages;
use App\Models\BusinessReferral;
use App\Services\ExportService;
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
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('export')
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('gray')
                        ->visible(fn () => auth()->user()?->can('export-data'))
                        ->action(function ($records) {
                            $records->loadMissing(['referrerBusiness', 'referredBusiness']);

                            return ExportService::streamCsvFromCollection(
                                'business-referrals-' . now()->format('Ymd-His') . '.csv',
                                [
                                    'Referral Code',
                                    'Referrer Business',
                                    'Referred Business',
                                    'Credits Awarded',
                                    'Status',
                                    'Suspicious',
                                    'IP Address',
                                    'Created At',
                                ],
                                $records,
                                fn (BusinessReferral $record) => [
                                    $record->referral_code,
                                    $record->referrerBusiness?->business_name,
                                    $record->referredBusiness?->business_name,
                                    $record->referral_credits_awarded,
                                    $record->status,
                                    $record->is_suspicious ? 'yes' : 'no',
                                    $record->ip_address,
                                    optional($record->created_at)->toDateTimeString(),
                                ]
                            );
                        }),
                ]),
            ]);
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
