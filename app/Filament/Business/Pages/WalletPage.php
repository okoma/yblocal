<?php
// ============================================
// app/Filament/Business/Pages/Wallet.php
// View wallet balance, transactions, add credits
// ============================================

namespace App\Filament\Business\Pages;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\PaymentGateway;
use App\Services\PaymentService;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletPage extends Page implements HasTable, HasActions
{
    use InteractsWithTable, InteractsWithActions;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    protected static ?string $navigationLabel = 'My Wallet';

    protected static ?string $navigationGroup = 'Billing & Marketing';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.business.pages.wallet';

    public function getWallet()
    {
        $user = auth()->user();
        
        // Get or create wallet
        return Wallet::firstOrCreate(
            ['user_id' => $user->id],
            [
                'balance' => 0,
                'currency' => 'NGN',
                'ad_credits' => 0,
            ]
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('add_funds')
                ->label('Add Funds')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->modalWidth('md')
                ->form([
                    Forms\Components\TextInput::make('amount')
                        ->label('Amount (₦)')
                        ->numeric()
                        ->required()
                        ->minValue(100)
                        ->maxValue(1000000)
                        ->prefix('₦')
                        ->live(debounce: 500)
                        ->helperText('Minimum: ₦100, Maximum: ₦1,000,000'),

                    Forms\Components\Select::make('payment_gateway_id')
                        ->label('Payment Method')
                        ->options(function () {
                            return PaymentGateway::where('is_active', true)
                                ->where('is_enabled', true)
                                ->where('slug', '!=', 'wallet') // Can't use wallet to fund wallet
                                ->pluck('name', 'id');
                        })
                        ->native(false)
                        ->required()
                        ->helperText('Select your preferred payment method'),
                ])
                ->action(function (array $data) {
                    return $this->processFunding($data);
                }),

            Action::make('buy_credits')
                ->label('Buy Ad Credits')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->modalWidth('md')
                ->form([
                    Forms\Components\Select::make('credit_package')
                        ->label('Select Credit Package')
                        ->options([
                            '100' => '100 Credits - ₦1,000',
                            '500' => '500 Credits - ₦5,000',
                            '1000' => '1,000 Credits - ₦10,000',
                            '2500' => '2,500 Credits - ₦25,000',
                            '5000' => '5,000 Credits - ₦50,000',
                            '10000' => '10,000 Credits - ₦100,000',
                            'custom' => 'Custom Amount',
                        ])
                        ->native(false)
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Forms\Set $set, $state) {
                            if ($state && $state !== 'custom') {
                                $set('credits', (int) $state);
                                $set('amount', (int) $state * 10);
                            } else {
                                $set('credits', null);
                                $set('amount', null);
                            }
                        })
                        ->helperText('Choose a package or enter custom amount'),

                    Forms\Components\TextInput::make('credits')
                        ->label('Custom Credits')
                        ->numeric()
                        ->required()
                        ->minValue(10)
                        ->maxValue(10000)
                        ->live(debounce: 500)
                        ->afterStateUpdated(fn (Forms\Set $set, $state) => 
                            $set('amount', $state ? ((int) $state * 10) : 0)
                        )
                        ->visible(fn (Forms\Get $get) => $get('credit_package') === 'custom')
                        ->helperText('Minimum: 10 credits, Maximum: 10,000 credits'),

                    Forms\Components\Placeholder::make('total_display')
                        ->label('Total Cost')
                        ->content(fn (Forms\Get $get) => 
                            '₦' . number_format(($get('credits') ?? 0) * 10, 2)
                        )
                        ->visible(fn (Forms\Get $get) => $get('credits') > 0),
                    
                    Forms\Components\Hidden::make('amount'),

                    Forms\Components\Select::make('payment_gateway_id')
                        ->label('Payment Method')
                        ->options(function () {
                            return PaymentGateway::where('is_active', true)
                                ->where('is_enabled', true)
                                ->pluck('name', 'id');
                        })
                        ->native(false)
                        ->required()
                        ->helperText('Select your preferred payment method'),
                ])
                ->action(function (array $data) {
                    return $this->processCreditPurchase($data);
                }),

            Action::make('withdraw')
                ->label('Withdraw Funds')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('danger')
                ->visible(fn () => $this->getWallet()->balance >= 1000)
                ->modalWidth('md')
                ->form([
                    Forms\Components\Placeholder::make('current_balance')
                        ->label('Current Balance')
                        ->content(fn () => '₦' . number_format($this->getWallet()->balance, 2)),
                    
                    Forms\Components\TextInput::make('amount')
                        ->label('Withdrawal Amount (₦)')
                        ->numeric()
                        ->required()
                        ->minValue(1000)
                        ->maxValue(fn () => $this->getWallet()->balance)
                        ->prefix('₦')
                        ->helperText('Minimum: ₦1,000 | Processing time: 24-48 hours'),

                    Forms\Components\TextInput::make('account_number')
                        ->label('Account Number')
                        ->required()
                        ->maxLength(10)
                        ->minLength(10)
                        ->numeric(),
                    
                    Forms\Components\TextInput::make('account_name')
                        ->label('Account Name')
                        ->required()
                        ->maxLength(255),
                    
                    Forms\Components\TextInput::make('bank_name')
                        ->label('Bank Name')
                        ->required()
                        ->maxLength(255),
                    
                    Forms\Components\Textarea::make('reason')
                        ->label('Reason for Withdrawal (Optional)')
                        ->rows(2)
                        ->maxLength(500),
                ])
                ->action(function (array $data) {
                    return $this->processWithdrawal($data);
                }),
        ];
    }
    
    /**
     * Process wallet funding
     */
    protected function processFunding(array $data): mixed
    {
        try {
            $user = auth()->user();
            $amount = $data['amount'];
            $gatewayId = $data['payment_gateway_id'];
            
            // Get or create wallet
            $wallet = $this->getWallet();
            
            DB::beginTransaction();
            
            try {
                // Initialize payment through service
                $result = app(PaymentService::class)->initializePayment(
                    user: $user,
                    amount: $amount,
                    gatewayId: $gatewayId,
                    payable: $wallet,
                    metadata: [
                        'type' => 'wallet_funding',
                        'amount' => $amount,
                    ]
                );
                
                DB::commit();
                
                // Handle payment result
                if ($result->requiresRedirect()) {
                    return redirect()->away($result->redirectUrl);
                } elseif ($result->isBankTransfer()) {
                    Notification::make()
                        ->info()
                        ->title('Bank Transfer Details')
                        ->body($result->instructions)
                        ->persistent()
                        ->send();
                    return null;
                } elseif ($result->isSuccess()) {
                    Notification::make()
                        ->success()
                        ->title('Success!')
                        ->body($result->message)
                        ->send();
                    return null;
                } else {
                    throw new \Exception($result->message);
                }
                
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
            
        } catch (\Exception $e) {
            Log::error('Wallet funding failed', [
                'user_id' => auth()->id(),
                'amount' => $data['amount'] ?? null,
                'error' => $e->getMessage(),
            ]);
            
            Notification::make()
                ->danger()
                ->title('Payment Error')
                ->body($e->getMessage() ?: 'Unable to process payment. Please try again.')
                ->send();
            
            return null;
        }
    }
    
    /**
     * Process ad credit purchase
     */
    protected function processCreditPurchase(array $data): mixed
    {
        try {
            $user = auth()->user();
            $credits = $data['credits'];
            $amount = $credits * 10; // 1 credit = ₦10
            $gatewayId = $data['payment_gateway_id'];
            
            // Check if using wallet payment
            $gateway = PaymentGateway::find($gatewayId);
            if (!$gateway) {
                throw new \Exception('Invalid payment method selected.');
            }
            
            if ($gateway->isWallet()) {
                // Direct wallet payment for credits
                $wallet = $this->getWallet();
                
                if (!$wallet->hasBalance($amount)) {
                    Notification::make()
                        ->danger()
                        ->title('Insufficient Balance')
                        ->body(sprintf(
                            'You need ₦%s more. Current balance: ₦%s',
                            number_format($amount - $wallet->balance, 2),
                            number_format($wallet->balance, 2)
                        ))
                        ->send();
                    return null;
                }
                
                DB::beginTransaction();
                
                try {
                    // Deduct from balance and add credits
                    $wallet->purchase($amount, "Purchased {$credits} ad credits");
                    $wallet->addCredits($credits, "Ad credits purchase - {$credits} credits");
                    
                    DB::commit();
                    
                    Notification::make()
                        ->success()
                        ->title('Credits Purchased!')
                        ->body("{$credits} ad credits added to your account.")
                        ->send();
                    
                    return null;
                    
                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e;
                }
            } else {
                // Gateway payment for credits - fund wallet first, then convert to credits
                Notification::make()
                    ->warning()
                    ->title('Feature Coming Soon')
                    ->body('Please add funds first, then buy credits using your wallet balance.')
                    ->send();
                return null;
            }
            
        } catch (\Exception $e) {
            Log::error('Credit purchase failed', [
                'user_id' => auth()->id(),
                'credits' => $data['credits'] ?? null,
                'error' => $e->getMessage(),
            ]);
            
            Notification::make()
                ->danger()
                ->title('Purchase Error')
                ->body($e->getMessage() ?: 'Unable to process purchase. Please try again.')
                ->send();
            
            return null;
        }
    }
    
    /**
     * Process withdrawal request
     */
    protected function processWithdrawal(array $data): void
    {
        try {
            $user = auth()->user();
            $wallet = $this->getWallet();
            $amount = $data['amount'];
            
            if (!$wallet->hasBalance($amount)) {
                Notification::make()
                    ->danger()
                    ->title('Insufficient Balance')
                    ->body('You do not have enough balance for this withdrawal.')
                    ->send();
                return;
            }
            
            DB::beginTransaction();
            
            try {
                // Create withdrawal transaction (pending)
                $wallet->withdraw(
                    $amount, 
                    "Withdrawal request to {$data['bank_name']} - {$data['account_number']}"
                );
                
                // TODO: Create a WithdrawalRequest model to track admin approval
                // For now, just create the transaction
                
                DB::commit();
                
                Notification::make()
                    ->success()
                    ->title('Withdrawal Requested')
                    ->body('Your withdrawal request has been submitted. Processing time: 24-48 hours.')
                    ->send();
                
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
            
        } catch (\Exception $e) {
            Log::error('Withdrawal failed', [
                'user_id' => auth()->id(),
                'amount' => $data['amount'] ?? null,
                'error' => $e->getMessage(),
            ]);
            
            Notification::make()
                ->danger()
                ->title('Withdrawal Error')
                ->body($e->getMessage() ?: 'Unable to process withdrawal. Please try again.')
                ->send();
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                WalletTransaction::where('user_id', auth()->id())
                    ->orderBy('created_at', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'deposit', 'refund', 'bonus' => 'success',
                        'withdrawal', 'purchase' => 'danger',
                        'credit_purchase' => 'primary',
                        'credit_usage' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state))),

                Tables\Columns\TextColumn::make('description')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('amount')
                    ->money('NGN')
                    ->sortable()
                    ->color(fn ($record) => in_array($record->type, ['deposit', 'refund', 'bonus']) ? 'success' : 'danger')
                    ->prefix(fn ($record) => in_array($record->type, ['deposit', 'refund', 'bonus']) ? '+' : '-'),

                Tables\Columns\TextColumn::make('credits')
                    ->label('Credits')
                    ->badge()
                    ->color('primary')
                    ->default(0)
                    ->formatStateUsing(fn ($state, $record) => $state > 0 ? $state : null)
                    ->prefix(fn ($record) => $record && in_array($record->type, ['credit_purchase', 'bonus']) ? '+' : '-'),

                Tables\Columns\TextColumn::make('balance_after')
                    ->label('Balance')
                    ->money('NGN')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'deposit' => 'Deposits',
                        'withdrawal' => 'Withdrawals',
                        'purchase' => 'Purchases',
                        'refund' => 'Refunds',
                        'credit_purchase' => 'Credit Purchases',
                        'credit_usage' => 'Credit Usage',
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }
}