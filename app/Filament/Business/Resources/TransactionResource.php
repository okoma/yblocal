<?php

namespace App\Filament\Business\Resources;

use App\Filament\Business\Resources\TransactionResource\Pages;
use App\Models\AdCampaign;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Services\ActiveBusiness;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Transactions';

    protected static ?string $navigationGroup = 'Billing & Marketing';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        $id = app(ActiveBusiness::class)->getActiveBusinessId();
        $query = parent::getEloquentQuery()
            ->with(['gateway', 'transactionable'])
            ->latest();
        if ($id === null) {
            return $query->whereRaw('1 = 0');
        }
        // Filter by business_id directly (now that transactions are business-scoped)
        return $query->where('business_id', $id);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // View only - Business users shouldn't create transactions manually
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_ref')
                    ->label('Reference')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Reference copied!')
                    ->tooltip('Click to copy'),

                Tables\Columns\TextColumn::make('transactionable_type')
                    ->label('Type')
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
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('NGN')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Method')
                    ->formatStateUsing(fn (string $state): string => Str::title(str_replace('_', ' ', $state)))
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'paystack' => 'success',
                        'flutterwave' => 'warning',
                        'bank_transfer' => 'info',
                        'wallet' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'completed' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        'refunded' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Paid At')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Payment Method')
                    ->options([
                        'paystack' => 'Paystack',
                        'flutterwave' => 'Flutterwave',
                        'bank_transfer' => 'Bank Transfer',
                        'wallet' => 'Wallet',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('transactionable_type')
                    ->label('Transaction Type')
                    ->options([
                        'App\Models\Subscription' => 'Subscription',
                        'App\Models\AdCampaign' => 'Ad Campaign',
                        'App\Models\Wallet' => 'Wallet Funding',
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('From'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                
                Tables\Actions\Action::make('download_receipt')
                    ->label('Receipt')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->visible(fn (Transaction $record) => $record->status === 'completed')
                    ->url(fn (Transaction $record) => route('business.transaction.receipt', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                // No bulk actions for business users
            ])
            ->emptyStateHeading('No transactions yet')
            ->emptyStateDescription('Your payment history will appear here.')
            ->emptyStateIcon('heroicon-o-banknotes')
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'view' => Pages\ViewTransaction::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Transactions are created automatically via payments
    }

    public static function getNavigationBadge(): ?string
    {
        $id = app(ActiveBusiness::class)->getActiveBusinessId();
        if ($id === null) {
            return null;
        }
        $pending = static::getModel()::where('business_id', $id)
            ->where('status', 'pending')
            ->count();
        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    protected static ?string $recordTitleAttribute = 'transaction_ref';

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return static::getEloquentQuery();
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->transaction_ref;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['transaction_ref', 'description'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Reference' => $record->transaction_ref,
            'Amount' => '₦' . number_format($record->amount, 2),
            'Status' => ucfirst($record->status),
            'Method' => ucfirst(str_replace('_', ' ', $record->payment_method)),
        ];
    }
      public static function canViewAny(): bool
    {
        return Auth::user()->isAdmin() 
            || Auth::user()->isModerator() 
            || Auth::user()->isBusinessOwner()
            || Auth::user()->isBusinessManager();
    }
}
