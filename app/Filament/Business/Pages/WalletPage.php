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
                ->label('Add Fund')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->modalWidth('lg')
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
                        ->live()
                        ->afterStateUpdated(function (Forms\Set $set) {
                            // Clear proof upload when payment method changes
                            $set('payment_proof', null);
                        })
                        ->helperText('Select your preferred payment method'),

                    // Payment Summary (shown for all payment methods)
                    Forms\Components\Section::make('Payment Summary')
                        ->schema([
                            Forms\Components\Placeholder::make('summary_amount')
                                ->label('Amount to Pay')
                                ->content(fn (Forms\Get $get) => '₦' . number_format($get('amount') ?? 0, 2))
                                ->extraAttributes(['class' => 'text-2xl font-bold text-primary-600 dark:text-primary-400']),
                        ])
                        ->visible(fn (Forms\Get $get) => $get('amount') > 0 && $get('payment_gateway_id'))
                        ->columnSpanFull(),

                    // Bank Transfer Details (shown only when bank transfer is selected)
                    Forms\Components\Section::make('Bank Transfer Details')
                        ->schema([
                            Forms\Components\Placeholder::make('bank_details')
                                ->label('')
                                ->content(function (Forms\Get $get) {
                                    $gatewayId = $get('payment_gateway_id');
                                    if (!$gatewayId) return '';
                                    
                                    $gateway = PaymentGateway::find($gatewayId);
                                    if (!$gateway || !$gateway->isBankTransfer()) return '';
                                    
                                    $bankDetails = $gateway->bank_account_details ?? [];
                                    $accountNumber = $bankDetails['account_number'] ?? 'N/A';
                                    $accountName = $bankDetails['account_name'] ?? 'N/A';
                                    $bankName = $bankDetails['bank_name'] ?? 'N/A';
                                    
                                    return view('filament.components.bank-transfer-details', [
                                        'accountNumber' => $accountNumber,
                                        'accountName' => $accountName,
                                        'bankName' => $bankName,
                                    ])->render();
                                })
                                ->columnSpanFull(),

                            Forms\Components\Placeholder::make('transfer_instructions')
                                ->label('')
                                ->content('After making the transfer, upload your payment proof below. Your wallet will be credited within 24-48 hours after verification.')
                                ->extraAttributes(['class' => 'text-sm text-gray-600 dark:text-gray-400']),
                        ])
                        ->visible(fn (Forms\Get $get) => $this->isBankTransferSelected($get('payment_gateway_id')))
                        ->collapsible()
                        ->collapsed(false),

                    // Payment Proof Section (separate)
                    Forms\Components\Section::make('Payment Proof')
                        ->schema([
                            Forms\Components\FileUpload::make('payment_proof')
                                ->label('Upload Proof of Payment')
                                ->helperText('Upload receipt or screenshot of your transfer (JPEG, PNG, or PDF)')
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'])
                                ->maxSize(5120) // 5MB
                                ->directory('transfer-proofs')
                                ->visibility('private')
                                ->required()
                                ->downloadable()
                                ->previewable()
                                ->columnSpanFull(),
                        ])
                        ->visible(fn (Forms\Get $get) => $this->isBankTransferSelected($get('payment_gateway_id')))
                        ->collapsible()
                        ->collapsed(false),
                ])
                ->action(function (array $data) {
                    return $this->processFunding($data);
                })
                ->modalSubmitActionLabel('Add Fund')
                ->modalFooterActionsAlignment('right'),

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
                })
                ->modalSubmitActionLabel('Buy Credits')
                ->modalFooterActionsAlignment('right'),

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
                })
                ->modalSubmitActionLabel('Withdraw Funds')
                ->modalFooterActionsAlignment('right'),
        ];
    }
    
    /**
     * Check if bank transfer is selected
     */
    protected function isBankTransferSelected(?int $gatewayId): bool
    {
        if (!$gatewayId) {
            return false;
        }
        
        $gateway = PaymentGateway::find($gatewayId);
        return $gateway && $gateway->isBankTransfer();
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
                // Get gateway to check if it's bank transfer
                $gateway = PaymentGateway::find($gatewayId);
                $isBankTransfer = $gateway && $gateway->isBankTransfer();
                
                // Prepare metadata
                $metadata = [
                    'type' => 'wallet_funding',
                    'amount' => $amount,
                ];
                
                // Add payment proof if bank transfer
                if ($isBankTransfer && !empty($data['payment_proof'])) {
                    $metadata['payment_proof'] = $data['payment_proof'];
                    $metadata['payment_proof_uploaded_at'] = now()->toIso8601String();
                }
                
                // Initialize payment through service
                $result = app(PaymentService::class)->initializePayment(
                    user: $user,
                    amount: $amount,
                    gatewayId: $gatewayId,
                    payable: $wallet,
                    metadata: $metadata
                );
                
                DB::commit();
                
                // Handle payment result
                if ($result->requiresRedirect()) {
                    return redirect()->away($result->redirectUrl);
                } elseif ($result->isBankTransfer()) {
                    // For bank transfer, show success message about proof upload
                    Notification::make()
                        ->success()
                        ->title('Payment Proof Uploaded')
                        ->body('Your payment proof has been received. Your wallet will be credited within 24-48 hours after verification.')
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
                // Create withdrawal transaction (deduct from wallet)
                $transaction = $wallet->withdraw(
                    $amount, 
                    "Withdrawal request to {$data['bank_name']} - {$data['account_number']}",
                    null, // No related transaction
                    [
                        'withdrawal_type' => 'bank_transfer',
                        'status' => 'pending_approval',
                    ]
                );
                
                // Create withdrawal request for admin approval
                $withdrawalRequest = \App\Models\WithdrawalRequest::create([
                    'user_id' => auth()->id(),
                    'wallet_id' => $wallet->id,
                    'amount' => $amount,
                    'bank_name' => $data['bank_name'],
                    'account_name' => $data['account_name'],
                    'account_number' => $data['account_number'],
                    'sort_code' => $data['sort_code'] ?? null,
                    'status' => 'pending',
                    'transaction_id' => $transaction->id,
                ]);
                
                Log::info('Withdrawal request created', [
                    'user_id' => auth()->id(),
                    'wallet_id' => $wallet->id,
                    'amount' => $amount,
                    'transaction_id' => $transaction->id,
                    'withdrawal_request_id' => $withdrawalRequest->id,
                    'bank_details' => [
                        'bank_name' => $data['bank_name'],
                        'account_number' => $data['account_number'],
                    ],
                ]);
                
                DB::commit();
                
                Notification::make()
                    ->success()
                    ->title('Withdrawal Requested')
                    ->body('Your withdrawal request has been submitted for approval. Processing time: 24-48 hours.')
                    ->send();
                
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Withdrawal request failed', [
                    'user_id' => auth()->id(),
                    'error' => $e->getMessage(),
                ]);
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