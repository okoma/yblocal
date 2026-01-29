<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CustomerReferralWithdrawalResource\Pages;
use App\Models\CustomerReferralWithdrawal;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class CustomerReferralWithdrawalResource extends Resource
{
    protected static ?string $model = CustomerReferralWithdrawal::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Customer Referral Withdrawals';
    protected static ?string $navigationGroup = 'Referral Program';
    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->url(fn (CustomerReferralWithdrawal $record) => UserResource::getUrl('view', ['record' => $record->user_id])),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('NGN')
                    ->sortable(),

                Tables\Columns\TextColumn::make('bank_name')->label('Bank')->searchable(),
                Tables\Columns\TextColumn::make('account_name')->label('Account Name')->searchable(),
                Tables\Columns\TextColumn::make('account_number')->label('Account Number')->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('processor.name')
                    ->label('Processed By')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('processed_at')
                    ->label('Processed At')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Requested At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected']),
                Tables\Filters\Filter::make('pending_only')
                    ->label('Pending Only')
                    ->query(fn (Builder $q) => $q->where('status', 'pending'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('approve')
                        ->label('Approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Approve Withdrawal')
                        ->modalDescription('Deduct the amount from the customer referral wallet and mark as approved.')
                        ->form([
                            Forms\Components\Textarea::make('notes')->label('Notes')->rows(2),
                        ])
                        ->action(function (CustomerReferralWithdrawal $record, array $data) {
                            if ($record->status !== 'pending') {
                                Notification::make()->warning()->title('Already processed')->send();
                                return;
                            }
                            $wallet = $record->customerReferralWallet;
                            if (!$wallet || (float) $wallet->balance < (float) $record->amount) {
                                Notification::make()->danger()->title('Insufficient balance')->body('Customer referral wallet has insufficient balance.')->send();
                                return;
                            }
                            $wallet->withdraw((float) $record->amount, 'Withdrawal approved (ID: ' . $record->id . ')', $record);
                            $record->approve(auth()->user(), $data['notes'] ?? null);
                            Notification::make()->success()->title('Withdrawal approved')->send();
                        })
                        ->visible(fn (CustomerReferralWithdrawal $record) => $record->isPending()),

                    Tables\Actions\Action::make('reject')
                        ->label('Reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Reject Withdrawal')
                        ->form([
                            Forms\Components\Textarea::make('rejection_reason')->label('Reason')->required()->rows(3),
                            Forms\Components\Textarea::make('notes')->label('Notes')->rows(2),
                        ])
                        ->action(function (CustomerReferralWithdrawal $record, array $data) {
                            if ($record->status !== 'pending') {
                                Notification::make()->warning()->title('Already processed')->send();
                                return;
                            }
                            $record->reject(auth()->user(), $data['rejection_reason'], $data['notes'] ?? null);
                            Notification::make()->success()->title('Withdrawal rejected')->send();
                        })
                        ->visible(fn (CustomerReferralWithdrawal $record) => $record->isPending()),

                    Tables\Actions\ViewAction::make(),
                ]),
            ])
            ->bulkActions([])
            ->emptyStateHeading('No Customer Referral Withdrawals')
            ->emptyStateDescription('Withdrawal requests from customers will appear here.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomerReferralWithdrawals::route('/'),
            'view' => Pages\ViewCustomerReferralWithdrawal::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = static::getModel()::where('status', 'pending')->count();
        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
