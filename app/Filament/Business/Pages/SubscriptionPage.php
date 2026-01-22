<?php
// ============================================
// SUBSCRIPTION PAGE
// app/Filament/Business/Pages/SubscriptionPage.php
// ============================================

namespace App\Filament\Business\Pages;

use App\Models\PaymentGateway;
use App\Models\Coupon;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class SubscriptionPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    
    protected static ?string $navigationLabel = 'Subscription';
    
    protected static ?string $navigationGroup = 'Billing & Marketing';
    
    protected static ?int $navigationSort = 7;

    protected static string $view = 'filament.business.pages.subscription-page';
    
    public ?int $selectedPlanId = null;
    public ?array $paymentData = [];
    public ?Coupon $appliedCoupon = null;
    public float $discountAmount = 0;
    public float $finalAmount = 0;
    public string $billingInterval = 'monthly'; // 'monthly' or 'yearly'
    
    public function getTitle(): string
    {
        return 'Subscription Plan';
    }
    
    public function getCurrentSubscription()
    {
        return Auth::user()->subscription()->with('plan')->first();
    }
    
    public function getAllPlans()
    {
        return \App\Models\SubscriptionPlan::where('is_active', true)
            ->orderBy('order')
            ->get();
    }
    
    public function mount(): void
    {
        $this->form->fill([
            'payment_gateway_id' => null,
            'coupon_code' => null,
        ]);
    }
    
    public function openPaymentModal(int $planId): void
    {
        $this->selectedPlanId = $planId;
        $plan = \App\Models\SubscriptionPlan::findOrFail($planId);
        $this->billingInterval = 'monthly';
        $this->paymentData = [
            'payment_gateway_id' => null,
            'coupon_code' => null,
        ];
        $this->appliedCoupon = null;
        $this->discountAmount = 0;
        $this->finalAmount = $plan->price;
        $this->form->fill($this->paymentData);
        
        // Use $dispatch with proper syntax for Filament 3
        $this->dispatch('open-modal', id: 'subscribe-modal');
    }
    
    public function updatedBillingInterval(): void
    {
        $this->updateFinalAmount();
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Coupon Code (Optional)')
                    ->schema([
                        Forms\Components\TextInput::make('coupon_code')
                            ->label('Coupon Code')
                            ->placeholder('Enter coupon code')
                            ->maxLength(50)
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('applyCoupon')
                                    ->label('Apply')
                                    ->icon('heroicon-o-check')
                                    ->action('applyCoupon')
                            )
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state) {
                                if (empty($state)) {
                                    $this->appliedCoupon = null;
                                    $this->discountAmount = 0;
                                    $this->updateFinalAmount();
                                } else {
                                    // Auto-validate and apply coupon when code is entered
                                    $this->paymentData['coupon_code'] = $state;
                                    $this->applyCoupon();
                                }
                            }),
                    ]),
                
                Forms\Components\Section::make('Payment Method')
                    ->schema([
                        Forms\Components\Select::make('payment_gateway_id')
                            ->label('Select Payment Method')
                            ->options(function () {
                                return PaymentGateway::enabled()
                                    ->ordered()
                                    ->get()
                                    ->mapWithKeys(function ($gateway) {
                                        return [$gateway->id => $gateway->display_name];
                                    });
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live(),
                    ]),
            ])
            ->statePath('paymentData');
    }
    
    public function applyCoupon(): void
    {
        $couponCode = $this->paymentData['coupon_code'] ?? null;
        
        if (empty($couponCode)) {
            $this->appliedCoupon = null;
            $this->discountAmount = 0;
            $this->updateFinalAmount();
            return;
        }
        
        $coupon = Coupon::where('code', strtoupper($couponCode))->first();
        
        if (!$coupon) {
            Notification::make()
                ->danger()
                ->title('Invalid Coupon')
                ->body('The coupon code you entered is invalid.')
                ->send();
            $this->appliedCoupon = null;
            $this->discountAmount = 0;
            $this->updateFinalAmount();
            return;
        }
        
        // Check if coupon applies to subscriptions
        if (!in_array($coupon->applies_to, ['all', 'subscriptions'])) {
            Notification::make()
                ->danger()
                ->title('Coupon Not Applicable')
                ->body('This coupon cannot be used for subscriptions.')
                ->send();
            $this->appliedCoupon = null;
            $this->discountAmount = 0;
            $this->updateFinalAmount();
            return;
        }
        
        // Check if coupon applies to this specific plan
        if ($coupon->applies_to === 'subscriptions' && $coupon->applicable_plans) {
            if (!in_array($this->selectedPlanId, $coupon->applicable_plans)) {
                Notification::make()
                    ->danger()
                    ->title('Coupon Not Applicable')
                    ->body('This coupon cannot be used for the selected plan.')
                    ->send();
                $this->appliedCoupon = null;
                $this->discountAmount = 0;
                $this->updateFinalAmount();
                return;
            }
        }
        
        // Check if coupon is valid
        if (!$coupon->isValid()) {
            Notification::make()
                ->danger()
                ->title('Coupon Expired')
                ->body('This coupon is no longer valid.')
                ->send();
            $this->appliedCoupon = null;
            $this->discountAmount = 0;
            $this->updateFinalAmount();
            return;
        }
        
        // Check if user can use this coupon
        if (!$coupon->canBeUsedBy(Auth::id())) {
            Notification::make()
                ->danger()
                ->title('Coupon Usage Limit')
                ->body('You have reached the usage limit for this coupon.')
                ->send();
            $this->appliedCoupon = null;
            $this->discountAmount = 0;
            $this->updateFinalAmount();
            return;
        }
        
        // Apply coupon
        $basePrice = $this->getCurrentPlanPrice();
        $this->appliedCoupon = $coupon;
        $this->discountAmount = $coupon->calculateDiscount($basePrice);
        $this->updateFinalAmount();
        
        Notification::make()
            ->success()
            ->title('Coupon Applied')
            ->body('Your discount has been applied!')
            ->send();
    }
    
    protected function updateFinalAmount(): void
    {
        $plan = \App\Models\SubscriptionPlan::findOrFail($this->selectedPlanId);
        $basePrice = $this->billingInterval === 'yearly' && $plan->yearly_price 
            ? $plan->yearly_price 
            : $plan->price;
        $this->finalAmount = max(0, $basePrice - $this->discountAmount);
    }
    
    public function getCurrentPlanPrice(): float
    {
        if (!$this->selectedPlanId) {
            return 0;
        }
        $plan = \App\Models\SubscriptionPlan::find($this->selectedPlanId);
        if (!$plan) {
            return 0;
        }
        return $this->billingInterval === 'yearly' && $plan->yearly_price 
            ? $plan->yearly_price 
            : $plan->price;
    }
    
    public function processPayment(): void
    {
        $data = $this->form->getState();
        $plan = \App\Models\SubscriptionPlan::findOrFail($this->selectedPlanId);
        $gateway = PaymentGateway::findOrFail($data['payment_gateway_id']);
        $user = Auth::user();
        
        // Calculate subscription duration based on billing interval
        $duration = $this->billingInterval === 'yearly' ? 12 : 1;
        
        // Create pending subscription
        $subscription = \App\Models\Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'pending',
            'starts_at' => now(),
            'ends_at' => now()->addMonths($duration),
            'payment_method' => $gateway->slug,
            'auto_renew' => true,
        ]);
        
        // Create transaction
        $transaction = \App\Models\Transaction::create([
            'user_id' => $user->id,
            'transaction_ref' => 'SUB-' . time() . '-' . $user->id,
            'transactionable_type' => \App\Models\Subscription::class,
            'transactionable_id' => $subscription->id,
            'amount' => $this->finalAmount,
            'currency' => 'NGN',
            'payment_method' => $gateway->slug,
            'status' => 'pending',
            'description' => 'Subscription payment for ' . $plan->name . ' (' . $this->billingInterval . ')',
            'metadata' => [
                'billing_interval' => $this->billingInterval,
                'original_amount' => $this->getCurrentPlanPrice(),
                'discount_amount' => $this->discountAmount,
                'coupon_code' => $this->appliedCoupon?->code,
                'coupon_id' => $this->appliedCoupon?->id,
            ],
        ]);
        
        // Apply coupon if one was used
        if ($this->appliedCoupon) {
            try {
                $basePrice = $this->getCurrentPlanPrice();
                $this->appliedCoupon->apply($user->id, $basePrice, $transaction->id);
            } catch (\Exception $e) {
                Notification::make()
                    ->warning()
                    ->title('Coupon Error')
                    ->body('Could not apply coupon: ' . $e->getMessage())
                    ->send();
            }
        }
        
        // Route to appropriate payment processor
        if ($gateway->isPaystack()) {
            $this->redirectToPaystack($transaction, $gateway);
        } elseif ($gateway->isFlutterwave()) {
            $this->redirectToFlutterwave($transaction, $gateway);
        } elseif ($gateway->isBankTransfer()) {
            $this->showBankTransferDetails($transaction, $gateway);
        } elseif ($gateway->isWallet()) {
            $this->processWalletPayment($transaction, $gateway, $plan);
        }
    }
    
    protected function redirectToPaystack($transaction, $gateway): void
    {
        if (!$gateway->public_key || !$gateway->secret_key) {
            Notification::make()
                ->danger()
                ->title('Payment Gateway Error')
                ->body('Paystack is not properly configured. Please contact support.')
                ->send();
            return;
        }

        // Initialize Paystack payment
        $callbackUrl = $gateway->callback_url ?? route('payment.paystack.callback');
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.paystack.co/transaction/initialize",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer " . $gateway->secret_key,
                "Content-Type: application/json",
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'email' => Auth::user()->email,
                'amount' => $this->finalAmount * 100, // Convert to kobo
                'reference' => $transaction->transaction_ref,
                'callback_url' => $callbackUrl,
                'metadata' => [
                    'transaction_id' => $transaction->id,
                    'user_id' => Auth::id(),
                    'plan_id' => $transaction->transactionable_id,
                ],
            ]),
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            Notification::make()
                ->danger()
                ->title('Payment Error')
                ->body('Failed to initialize payment: ' . $err)
                ->send();
            return;
        }

        $result = json_decode($response, true);

        if ($result && $result['status'] && isset($result['data']['authorization_url'])) {
            // Redirect to Paystack payment page
            redirect($result['data']['authorization_url'])->send();
        } else {
            Notification::make()
                ->danger()
                ->title('Payment Error')
                ->body($result['message'] ?? 'Failed to initialize payment.')
                ->send();
        }
    }
    
    protected function redirectToFlutterwave($transaction, $gateway): void
    {
        if (!$gateway->public_key || !$gateway->secret_key) {
            Notification::make()
                ->danger()
                ->title('Payment Gateway Error')
                ->body('Flutterwave is not properly configured. Please contact support.')
                ->send();
            return;
        }

        // Initialize Flutterwave payment
        $callbackUrl = $gateway->callback_url ?? route('payment.flutterwave.callback');
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.flutterwave.com/v3/payments",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer " . $gateway->secret_key,
                "Content-Type: application/json",
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'tx_ref' => $transaction->transaction_ref,
                'amount' => $this->finalAmount,
                'currency' => 'NGN',
                'payment_options' => 'card,banktransfer,ussd',
                'redirect_url' => $callbackUrl,
                'customer' => [
                    'email' => Auth::user()->email,
                    'name' => Auth::user()->name,
                ],
                'customizations' => [
                    'title' => 'Subscription Payment',
                    'description' => $transaction->description,
                ],
                'meta' => [
                    'transaction_id' => $transaction->id,
                    'user_id' => Auth::id(),
                    'plan_id' => $transaction->transactionable_id,
                ],
            ]),
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            Notification::make()
                ->danger()
                ->title('Payment Error')
                ->body('Failed to initialize payment: ' . $err)
                ->send();
            return;
        }

        $result = json_decode($response, true);

        if ($result && $result['status'] === 'success' && isset($result['data']['link'])) {
            // Redirect to Flutterwave payment page
            redirect($result['data']['link'])->send();
        } else {
            Notification::make()
                ->danger()
                ->title('Payment Error')
                ->body($result['message'] ?? 'Failed to initialize payment.')
                ->send();
        }
    }
    
    protected function showBankTransferDetails($transaction, $gateway): void
    {
        // Show bank transfer details
        Notification::make()
            ->info()
            ->title('Bank Transfer')
            ->body('Bank transfer details will be shown here.')
            ->send();
    }
    
    protected function processWalletPayment($transaction, $gateway, $plan): void
    {
        $user = Auth::user();
        $wallet = $user->wallet;
        
        if ($wallet->balance < $this->finalAmount) {
            Notification::make()
                ->danger()
                ->title('Insufficient Balance')
                ->body('Your wallet balance is insufficient. Please add funds first.')
                ->send();
            return;
        }
        
        // Deduct from wallet
        $wallet->withdraw(
            $this->finalAmount,
            'Subscription payment for ' . $plan->name . ($this->appliedCoupon ? ' (Coupon: ' . $this->appliedCoupon->code . ')' : ''),
            $transaction
        );
        
        // Update transaction
        $transaction->update([
            'status' => 'completed',
        ]);
        
        // Activate subscription
        $subscription = $transaction->transactionable;
        $subscription->update([
            'status' => 'active',
        ]);
        
        Notification::make()
            ->success()
            ->title('Payment Successful')
            ->body('Your subscription has been activated!' . ($this->appliedCoupon ? ' Coupon applied: ' . $this->appliedCoupon->code : ''))
            ->send();
        
        $this->dispatch('close-modal', id: 'subscribe-modal');
        $this->selectedPlanId = null;
        $this->appliedCoupon = null;
        $this->discountAmount = 0;
    }
}
