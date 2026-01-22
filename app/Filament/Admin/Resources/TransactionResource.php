<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TransactionResource\Pages;
use App\Models\Transaction;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Transactions';

    protected static ?string $navigationGroup = 'Financial';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Transaction Details')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('User')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\TextInput::make('transaction_ref')
                            ->label('Transaction Reference')
                            ->required()
                            ->maxLength(255)
                            ->default(fn () => Transaction::generateRef())
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('payment_gateway_ref')
                            ->label('Gateway Reference')
                            ->maxLength(255),

                        Forms\Components\Select::make('transactionable_type')
                            ->label('Transaction Type')
                            ->options([
                                'App\Models\Subscription' => 'Subscription',
                                'App\Models\AdCampaign' => 'Ad Campaign',
                                'App\Models\Wallet' => 'Wallet Funding',
                            ])
                            ->required()
                            ->live(),

                        Forms\Components\TextInput::make('transactionable_id')
                            ->label('Related ID')
                            ->numeric()
                            ->required(),

                        Forms\Components\TextInput::make('amount')
                            ->label('Amount')
                            ->numeric()
                            ->prefix('₦')
                            ->required(),

                        Forms\Components\TextInput::make('currency')
                            ->label('Currency')
                            ->default('NGN')
                            ->maxLength(3),

                        Forms\Components\Select::make('payment_method')
                            ->label('Payment Method')
                            ->options([
                                'paystack' => 'Paystack',
                                'flutterwave' => 'Flutterwave',
                                'bank_transfer' => 'Bank Transfer',
                                'wallet' => 'Wallet',
                            ])
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'completed' => 'Completed',
                                'failed' => 'Failed',
                                'refunded' => 'Refunded',
                            ])
                            ->required()
                            ->default('pending'),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->maxLength(500)
                            ->columnSpanFull(),

                        Forms\Components\KeyValue::make('metadata')
                            ->label('Metadata')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Refund Information')
                    ->schema([
                        Forms\Components\Toggle::make('is_refunded')
                            ->label('Refunded')
                            ->default(false),

                        Forms\Components\TextInput::make('refund_amount')
                            ->label('Refund Amount')
                            ->numeric()
                            ->prefix('₦'),

                        Forms\Components\Textarea::make('refund_reason')
                            ->label('Refund Reason')
                            ->maxLength(500)
                            ->columnSpanFull(),

                        Forms\Components\DateTimePicker::make('refunded_at')
                            ->label('Refunded At'),
                    ])
                    ->columns(2)
                    ->collapsed(),

                Forms\Components\Section::make('Gateway Response')
                    ->schema([
                        Forms\Components\KeyValue::make('gateway_response')
                            ->label('Gateway Response Data')
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
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

                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => $record->user ? route('filament.admin.resources.users.view', $record->user) : null),

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

                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('NGN')
                    ->sortable(),

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

                Tables\Columns\IconColumn::make('is_refunded')
                    ->label('Refunded')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Paid At')
                    ->dateTime('M j, Y g:i A')
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

                Tables\Filters\TernaryFilter::make('is_refunded')
                    ->label('Refunded')
                    ->placeholder('All')
                    ->trueLabel('Refunded Only')
                    ->falseLabel('Not Refunded'),

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
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\Action::make('refund')
                    ->label('Refund')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn (Transaction $record) => $record->status === 'completed' && !$record->is_refunded)
                    ->form([
                        Forms\Components\TextInput::make('refund_amount')
                            ->label('Refund Amount')
                            ->numeric()
                            ->prefix('₦')
                            ->required()
                            ->default(fn (Transaction $record) => $record->amount),
                        
                        Forms\Components\Textarea::make('refund_reason')
                            ->label('Refund Reason')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(function (Transaction $record, array $data): void {
                        $record->refund($data['refund_amount'], $data['refund_reason']);
                        
                        // Send notification
                        \Filament\Notifications\Notification::make()
                            ->title('Transaction Refunded')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
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
            'create' => Pages\CreateTransaction::route('/create'),
            'view' => Pages\ViewTransaction::route('/{record}'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
