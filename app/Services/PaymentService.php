<?php

namespace App\Services;

use App\Models\PaymentGateway;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Subscription;
use App\Models\AdCampaign;
use App\Models\Wallet;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class PaymentService
{
    public function __construct(
        protected ActivationService $activationService
    ) {}

    // Constants
    protected const CURRENCY = 'NGN';
    protected const HTTP_TIMEOUT = 30;
    protected const MIN_AMOUNT = 100;
    
    // Transaction prefixes
    protected const PREFIX_SUBSCRIPTION = 'SUB';
    protected const PREFIX_CAMPAIGN = 'CAM';
    protected const PREFIX_WALLET = 'WAL';
    
    /**
     * Initialize payment for any payable entity
     * 
     * @param User $user
     * @param float $amount
     * @param int $gatewayId
     * @param Model $payable (Subscription, AdCampaign, Wallet)
     * @param array $metadata
     * @return PaymentResult
     */
    public function initializePayment(
        User $user,
        float $amount,
        int $gatewayId,
        Model $payable,
        array $metadata = []
    ): PaymentResult {
        try {
            // Validate user
            if (!$user || !$user->id) {
                Log::error('Invalid user for payment initialization');
                return PaymentResult::failed('Invalid user. Please log in and try again.');
            }
            
            // Validate amount is positive and numeric
            if (!is_numeric($amount) || $amount <= 0) {
                Log::warning('Invalid payment amount', [
                    'user_id' => $user->id,
                    'amount' => $amount,
                ]);
                return PaymentResult::failed('Invalid payment amount.');
            }
            
            // Validate minimum amount
            if ($amount < self::MIN_AMOUNT) {
                return PaymentResult::failed("Amount must be at least ₦" . number_format(self::MIN_AMOUNT, 2));
            }
            
            // Validate payable entity exists
            if (!$payable || !$payable->id) {
                Log::error('Invalid payable entity for payment', [
                    'user_id' => $user->id,
                    'payable_type' => get_class($payable ?? new \stdClass()),
                ]);
                return PaymentResult::failed('Invalid payment target. Please try again.');
            }
            
            // Validate payable entity is supported
            $validPayableTypes = [Subscription::class, AdCampaign::class, Wallet::class];
            if (!in_array(get_class($payable), $validPayableTypes)) {
                Log::error('Unsupported payable type', [
                    'user_id' => $user->id,
                    'type' => get_class($payable),
                ]);
                return PaymentResult::failed('Unsupported payment type.');
            }
            
            // Validate and get gateway
            $gateway = $this->validateGateway($gatewayId);
            
            // Create transaction
            $transaction = $this->createTransaction($user, $amount, $gateway, $payable, $metadata);
            
            // Log payment initiation
            Log::info('Payment initiated', [
                'user_id' => $user->id,
                'transaction_id' => $transaction->id,
                'gateway' => $gateway->slug,
                'amount' => $amount,
                'type' => get_class($payable),
                'payable_id' => $payable->id,
            ]);
            
            // Route to appropriate gateway
            return $this->routeToGateway($transaction, $gateway, $user, $amount);
            
        } catch (\Exception $e) {
            Log::error('Payment initialization failed', [
                'user_id' => $user->id ?? null,
                'amount' => $amount ?? null,
                'gateway_id' => $gatewayId ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return PaymentResult::failed('Unable to initialize payment. Please try again.');
        }
    }
    
    /**
     * Validate gateway exists and is active
     */
    protected function validateGateway(int $gatewayId): PaymentGateway
    {
        $gateway = PaymentGateway::where('id', $gatewayId)
            ->where('is_active', true)
            ->where('is_enabled', true)
            ->first();
        
        if (!$gateway) {
            throw new \Exception('Selected payment method is not available.');
        }
        
        return $gateway;
    }
    
    /**
     * Create transaction record
     */
    protected function createTransaction(
        User $user,
        float $amount,
        PaymentGateway $gateway,
        Model $payable,
        array $metadata
    ): Transaction {
        $prefix = $this->getTransactionPrefix($payable);
        
        // Get business_id from payable object
        $businessId = null;
        if (method_exists($payable, 'getBusinessId')) {
            $businessId = $payable->getBusinessId();
        } elseif (isset($payable->business_id)) {
            $businessId = $payable->business_id;
        } elseif ($payable instanceof \App\Models\Subscription) {
            $businessId = $payable->business_id;
        } elseif ($payable instanceof \App\Models\AdCampaign) {
            $businessId = $payable->business_id;
        } elseif ($payable instanceof \App\Models\Wallet) {
            $businessId = $payable->business_id;
        }
        
        if (!$businessId) {
            throw new \Exception('Cannot determine business_id for transaction. Payable object must have business_id.');
        }
        
        return Transaction::create([
            'user_id' => $user->id,
            'business_id' => $businessId,
            'payment_gateway_id' => $gateway->id,
            'transaction_ref' => $this->generateReference($prefix),
            'transactionable_type' => get_class($payable),
            'transactionable_id' => $payable->id,
            'amount' => $amount,
            'currency' => self::CURRENCY,
            'payment_method' => $gateway->slug,
            'status' => 'pending',
            'description' => $this->generateDescription($payable),
            'metadata' => $metadata,
        ]);
    }
    
    /**
     * Generate unique transaction reference
     */
    protected function generateReference(string $prefix): string
    {
        return $prefix . '-' . Str::upper(Str::random(10)) . '-' . time();
    }
    
    /**
     * Get transaction prefix based on payable type
     */
    protected function getTransactionPrefix(Model $payable): string
    {
        return match (get_class($payable)) {
            Subscription::class => self::PREFIX_SUBSCRIPTION,
            AdCampaign::class => self::PREFIX_CAMPAIGN,
            Wallet::class => self::PREFIX_WALLET,
            default => 'TXN',
        };
    }
    
    /**
     * Generate transaction description
     */
    protected function generateDescription(Model $payable): string
    {
        return match (get_class($payable)) {
            Subscription::class => 'Subscription: ' . $payable->plan->name,
            AdCampaign::class => 'Ad Campaign: ' . ($payable->package->name ?? 'Custom'),
            Wallet::class => 'Wallet Funding',
            default => 'Payment',
        };
    }
    
    /**
     * Route to appropriate payment gateway
     */
    protected function routeToGateway(
        Transaction $transaction,
        PaymentGateway $gateway,
        User $user,
        float $amount
    ): PaymentResult {
        return match (true) {
            $gateway->isPaystack() => $this->initializePaystack($transaction, $gateway, $user, $amount),
            $gateway->isFlutterwave() => $this->initializeFlutterwave($transaction, $gateway, $user, $amount),
            $gateway->isBankTransfer() => $this->initializeBankTransfer($transaction, $gateway),
            $gateway->isWallet() => $this->initializeWallet($transaction, $user, $amount),
            default => PaymentResult::failed('Payment method not supported.'),
        };
    }
    
    /**
     * Initialize Paystack payment
     */
    protected function initializePaystack(
        Transaction $transaction,
        PaymentGateway $gateway,
        User $user,
        float $amount
    ): PaymentResult {
        // Validate configuration
        if (!$gateway->secret_key || !$gateway->public_key) {
            Log::error('Paystack misconfigured', ['gateway_id' => $gateway->id]);
            return PaymentResult::failed('Payment method is temporarily unavailable.');
        }
        
        try {
            $response = Http::timeout(self::HTTP_TIMEOUT)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $gateway->secret_key,
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.paystack.co/transaction/initialize', [
                    'email' => $user->email,
                    'amount' => round($amount * 100), // Convert to kobo
                    'reference' => $transaction->transaction_ref,
                    'callback_url' => url('/payment/paystack/callback'),
                    'metadata' => [
                        'transaction_id' => $transaction->id,
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                    ],
                ]);
            
            if ($response->successful() && $response->json('status')) {
                $authUrl = $response->json('data.authorization_url');
                
                if ($authUrl) {
                    Log::info('Paystack payment initialized', [
                        'transaction_id' => $transaction->id,
                        'reference' => $transaction->transaction_ref,
                    ]);
                    
                    return PaymentResult::redirect($authUrl);
                }
            }
            
            Log::error('Paystack initialization failed', [
                'transaction_id' => $transaction->id,
                'response' => $response->json(),
                'status' => $response->status(),
            ]);
            
            return PaymentResult::failed('Unable to initialize payment. Please try again.');
            
        } catch (\Exception $e) {
            Log::error('Paystack exception', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
            
            return PaymentResult::failed('Payment initialization error. Please try again.');
        }
    }
    
    /**
     * Initialize Flutterwave payment
     */
    protected function initializeFlutterwave(
        Transaction $transaction,
        PaymentGateway $gateway,
        User $user,
        float $amount
    ): PaymentResult {
        // Validate configuration
        if (!$gateway->secret_key || !$gateway->public_key) {
            Log::error('Flutterwave misconfigured', ['gateway_id' => $gateway->id]);
            return PaymentResult::failed('Payment method is temporarily unavailable.');
        }
        
        try {
            $response = Http::timeout(self::HTTP_TIMEOUT)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $gateway->secret_key,
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.flutterwave.com/v3/payments', [
                    'tx_ref' => $transaction->transaction_ref,
                    'amount' => $amount,
                    'currency' => self::CURRENCY,
                    'payment_options' => 'card,banktransfer,ussd',
                    'redirect_url' => url('/payment/flutterwave/callback'),
                    'customer' => [
                        'email' => $user->email,
                        'name' => $user->name,
                    ],
                    'customizations' => [
                        'title' => config('app.name') . ' Payment',
                        'description' => $transaction->description,
                    ],
                    'meta' => [
                        'transaction_id' => $transaction->id,
                        'user_id' => $user->id,
                    ],
                ]);
            
            if ($response->successful() && $response->json('status') === 'success') {
                $paymentLink = $response->json('data.link');
                
                if ($paymentLink) {
                    Log::info('Flutterwave payment initialized', [
                        'transaction_id' => $transaction->id,
                        'reference' => $transaction->transaction_ref,
                    ]);
                    
                    return PaymentResult::redirect($paymentLink);
                }
            }
            
            Log::error('Flutterwave initialization failed', [
                'transaction_id' => $transaction->id,
                'response' => $response->json(),
                'status' => $response->status(),
            ]);
            
            return PaymentResult::failed('Unable to initialize payment. Please try again.');
            
        } catch (\Exception $e) {
            Log::error('Flutterwave exception', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
            
            return PaymentResult::failed('Payment initialization error. Please try again.');
        }
    }
    
    /**
     * Initialize bank transfer payment
     */
    protected function initializeBankTransfer(
        Transaction $transaction,
        PaymentGateway $gateway
    ): PaymentResult {
        try {
            $bankAccount = $gateway->bank_account_details;
            
            if (!$bankAccount || empty($bankAccount['account_number'])) {
                Log::error('Bank transfer details not configured', ['gateway_id' => $gateway->id]);
                return PaymentResult::failed('Bank transfer is currently unavailable.');
            }
            
            $instructions = sprintf(
                "Transfer ₦%s to:\n\nAccount Name: %s\nAccount Number: %s\nBank: %s\n%s\n\nReference: %s\n\n%s",
                number_format($transaction->amount, 2),
                $bankAccount['account_name'] ?? 'N/A',
                $bankAccount['account_number'],
                $bankAccount['bank_name'] ?? 'N/A',
                !empty($bankAccount['sort_code']) ? "Sort Code: {$bankAccount['sort_code']}" : '',
                $transaction->transaction_ref,
                $gateway->instructions ?? 'Payment will be verified within 24 hours.'
            );
            
            Log::info('Bank transfer instructions provided', [
                'transaction_id' => $transaction->id,
                'amount' => $transaction->amount,
            ]);
            
            return PaymentResult::bankTransfer($instructions);
            
        } catch (\Exception $e) {
            Log::error('Bank transfer error', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
            
            return PaymentResult::failed('Unable to retrieve bank details. Please contact support.');
        }
    }
    
    /**
     * Initialize wallet payment
     */
    protected function initializeWallet(
        Transaction $transaction,
        User $user,
        float $amount
    ): PaymentResult {
        try {
            $wallet = $user->wallet;
            
            // Validate wallet exists
            if (!$wallet) {
                Log::error('Wallet not found for user', ['user_id' => $user->id]);
                return PaymentResult::failed('Wallet not found. Please contact support.');
            }
            
            // Validate amount is positive
            if ($amount <= 0) {
                Log::warning('Invalid wallet payment amount', [
                    'user_id' => $user->id,
                    'amount' => $amount,
                ]);
                return PaymentResult::failed('Invalid payment amount.');
            }
            
            // Check sufficient balance
            if ($wallet->balance < $amount) {
                $shortfall = $amount - $wallet->balance;
                Log::info('Insufficient wallet balance', [
                    'user_id' => $user->id,
                    'required' => $amount,
                    'available' => $wallet->balance,
                    'shortfall' => $shortfall,
                ]);
                
                return PaymentResult::failed(sprintf(
                    'Insufficient balance. You need ₦%s more. Current balance: ₦%s',
                    number_format($shortfall, 2),
                    number_format($wallet->balance, 2)
                ));
            }
            
            // Validate transaction has a payable entity
            if (!$transaction->transactionable) {
                Log::error('Transaction has no payable entity', [
                    'transaction_id' => $transaction->id,
                ]);
                return PaymentResult::failed('Invalid transaction. Please contact support.');
            }
            
            // Use database transaction for consistency
            DB::beginTransaction();
            
            try {
                // Deduct from wallet
                $wallet->withdraw($amount, $transaction->description, $transaction);
                
                // Complete transaction and activate payable (subscription, campaign, etc.)
                $this->activationService->completeAndActivate($transaction);
                
                DB::commit();
                
                Log::info('Wallet payment completed', [
                    'user_id' => $user->id,
                    'transaction_id' => $transaction->id,
                    'amount' => $amount,
                    'wallet_balance_after' => $wallet->fresh()->balance,
                ]);
                
                return PaymentResult::success('Payment successful! Your purchase has been activated.');
                
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Wallet payment database transaction failed', [
                    'user_id' => $user->id,
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
            
        } catch (\Exception $e) {
            Log::error('Wallet payment failed', [
                'user_id' => $user->id,
                'transaction_id' => $transaction->id ?? 'N/A',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return PaymentResult::failed('Wallet payment failed. Please try again.');
        }
    }
}

/**
 * Payment Result DTO
 */
class PaymentResult
{
    public function __construct(
        public bool $success,
        public ?string $redirectUrl = null,
        public ?string $message = null,
        public ?string $instructions = null,
        public string $type = 'redirect' // redirect, bank_transfer, success, failed
    ) {}
    
    public static function redirect(string $url): self
    {
        return new self(true, $url, null, null, 'redirect');
    }
    
    public static function bankTransfer(string $instructions): self
    {
        return new self(true, null, null, $instructions, 'bank_transfer');
    }
    
    public static function success(string $message): self
    {
        return new self(true, null, $message, null, 'success');
    }
    
    public static function failed(string $message): self
    {
        return new self(false, null, $message, null, 'failed');
    }
    
    public function requiresRedirect(): bool
    {
        return $this->type === 'redirect' && !empty($this->redirectUrl);
    }
    
    public function isBankTransfer(): bool
    {
        return $this->type === 'bank_transfer';
    }
    
    public function isSuccess(): bool
    {
        return $this->success && $this->type === 'success';
    }
    
    public function isFailed(): bool
    {
        return !$this->success;
    }
}
