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
use Filament\Actions\Action;
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
    public ?string $couponError = null;
    
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
    
    public function getSubscribeAction(int $planId): Action
    {
        $plan = \App\Models\SubscriptionPlan::findOrFail($planId);
        
        return Action::make('subscribe_' . $planId)
            ->label('Subscribe Now')
            ->icon('heroicon-o-credit-card')
            ->color($plan->is_popular ? 'primary' : 'gray')
            ->size('lg')
            ->extraAttributes(['class' => 'w-full mt-6'])
            ->modalWidth('3xl')
            ->modalHeading(function () use ($plan) {
                return view('filament.business.pages.subscription-modal-heading', ['plan' => $plan]);
            })
            ->form(function () use ($plan) {
                return $this->getPaymentFormSchema($plan);
            })
            ->action(function (array $data) use ($plan) {
                return $this->processPaymentFromAction($plan, $data);
            })
            ->modalId('subscribe-modal-' . $planId);
    }
    
    protected function getPaymentFormSchema($plan): array
    {
        return [
            Forms\Components\Section::make('Billing Period')
                ->schema([
                    Forms\Components\Select::make('billing_interval')
                        ->label('')
                        ->options([
                            'monthly' => 'Monthly',
                            'yearly' => 'Yearly',
                        ])
                        ->default('monthly')
                        ->required()
                        ->native(false)
                        ->live()
                        ->inline(),
                ])
                ->visible(fn () => $plan->yearly_price !== null),
            
            Forms\Components\Section::make('Coupon Code (Optional)')
                ->schema([
                    Forms\Components\TextInput::make('coupon_code')
                        ->label('Coupon Code')
                        ->placeholder('Enter coupon code')
                        ->maxLength(50)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) use ($plan) {
                            $this->validateAndApplyCoupon($state, $plan->id, $get('billing_interval') ?? 'monthly', $set);
                        }),
                    Forms\Components\Placeholder::make('coupon_message')
                        ->label('')
                        ->content(function (Forms\Get $get) {
                            $couponCode = $get('coupon_code');
                            if (empty($couponCode)) {
                                return null;
                            }
                            
                            $coupon = Coupon::where('code', strtoupper($couponCode))->first();
                            if (!$coupon) {
                                return new \Illuminate\Support\HtmlString(
                                    '<div class="text-danger-600 dark:text-danger-400 text-sm">Invalid coupon code.</div>'
                                );
                            }
                            
                            if (!$coupon->isValid()) {
                                return new \Illuminate\Support\HtmlString(
                                    '<div class="text-danger-600 dark:text-danger-400 text-sm">This coupon has expired.</div>'
                                );
                            }
                            
                            return new \Illuminate\Support\HtmlString(
                                '<div class="text-success-600 dark:text-success-400 text-sm">✓ Coupon valid: ' . $coupon->code . '</div>'
                            );
                        })
                        ->visible(fn (Forms\Get $get) => !empty($get('coupon_code'))),
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
                        ->preload(),
                ]),
            
            Forms\Components\Section::make('Order Summary')
                ->schema([
                    Forms\Components\Placeholder::make('summary')
                        ->label('')
                        ->content(function (Forms\Get $get) use ($plan) {
                            $billingInterval = $get('billing_interval') ?? 'monthly';
                            $basePrice = $billingInterval === 'yearly' && $plan->yearly_price 
                                ? $plan->yearly_price 
                                : $plan->price;
                            
                            $couponCode = $get('coupon_code');
                            $discount = 0;
                            if (!empty($couponCode)) {
                                $coupon = Coupon::where('code', strtoupper($couponCode))->first();
                                if ($coupon && $coupon->isValid() && $this->isCouponApplicable($coupon, $plan->id, $basePrice)) {
                                    $discount = $coupon->calculateDiscount($basePrice);
                                }
                            }
                            
                            $finalAmount = max(0, $basePrice - $discount);
                            
                            return new \Illuminate\Support\HtmlString(
                                '<div class="space-y-2">' .
                                '<div class="flex justify-between"><span>Plan:</span><span class="font-semibold">' . $plan->name . ' (' . ucfirst($billingInterval) . ')</span></div>' .
                                '<div class="flex justify-between"><span>Price:</span><span class="font-semibold">₦' . number_format($basePrice, 2) . '</span></div>' .
                                ($discount > 0 ? '<div class="flex justify-between text-success-600"><span>Discount:</span><span class="font-semibold">-₦' . number_format($discount, 2) . '</span></div>' : '') .
                                '<div class="flex justify-between pt-2 border-t"><span class="font-bold">Total:</span><span class="text-2xl font-bold text-primary-600">₦' . number_format($finalAmount, 2) . '</span></div>' .
                                '</div>'
                            );
                        }),
                ]),
        ];
    }
    
    protected function recalculateAmount($plan, $billingInterval, $couponCode, Forms\Set $set): void
    {
        // This will be handled by the summary placeholder's live update
    }
    
    protected function validateAndApplyCoupon($couponCode, $planId, $billingInterval, Forms\Set $set): void
    {
        // Validation happens in the summary placeholder
    }
    
    protected function isCouponApplicable($coupon, $planId, $basePrice): bool
    {
        if (!in_array($coupon->applies_to, ['all', 'subscriptions'])) {
            return false;
        }
        
        if ($coupon->applies_to === 'subscriptions' && $coupon->applicable_plans) {
            if (!in_array($planId, $coupon->applicable_plans)) {
                return false;
            }
        }
        
        if ($coupon->min_purchase_amount > 0 && $basePrice < $coupon->min_purchase_amount) {
            return false;
        }
        
        if (!$coupon->canBeUsedBy(Auth::id())) {
            return false;
        }
        
        return true;
    }
    
    protected function processPaymentFromAction($plan, array $data): \Illuminate\Http\RedirectResponse | null
    {
        $gateway = PaymentGateway::findOrFail($data['payment_gateway_id']);
        $user = Auth::user();
        $billingInterval = $data['billing_interval'] ?? 'monthly';
        
        // Calculate prices
        $basePrice = $billingInterval === 'yearly' && $plan->yearly_price 
            ? $plan->yearly_price 
            : $plan->price;
        
        $discount = 0;
        $coupon = null;
        if (!empty($data['coupon_code'])) {
            $coupon = Coupon::where('code', strtoupper($data['coupon_code']))->first();
            if ($coupon && $coupon->isValid() && $this->isCouponApplicable($coupon, $plan->id, $basePrice)) {
                $discount = $coupon->calculateDiscount($basePrice);
            }
        }
        
        $finalAmount = max(0, $basePrice - $discount);
        
        // Calculate subscription duration
        $duration = $billingInterval === 'yearly' ? 12 : 1;
        
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
            'amount' => $finalAmount,
            'currency' => 'NGN',
            'payment_method' => $gateway->slug,
            'status' => 'pending',
            'description' => 'Subscription payment for ' . $plan->name . ' (' . $billingInterval . ')',
            'metadata' => [
                'billing_interval' => $billingInterval,
                'original_amount' => $basePrice,
                'discount_amount' => $discount,
                'coupon_code' => $coupon?->code,
                'coupon_id' => $coupon?->id,
            ],
        ]);
        
        // Apply coupon if one was used
        if ($coupon) {
            try {
                $coupon->apply($user->id, $basePrice, $transaction->id);
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
            return $this->redirectToPaystackFromAction($transaction, $gateway, $finalAmount);
        } elseif ($gateway->isFlutterwave()) {
            return $this->redirectToFlutterwaveFromAction($transaction, $gateway, $finalAmount);
        } elseif ($gateway->isBankTransfer()) {
            $this->showBankTransferDetails($transaction, $gateway);
        } elseif ($gateway->isWallet()) {
            $this->processWalletPayment($transaction, $gateway, $plan, $finalAmount);
        }

        return null;
    }
    
    protected function redirectToPaystackFromAction($transaction, $gateway, $finalAmount): \Illuminate\Http\RedirectResponse
    {
        if (!$gateway->public_key || !$gateway->secret_key) {
            Notification::make()
                ->danger()
                ->title('Payment Gateway Error')
                ->body('Paystack is not properly configured. Please contact support.')
                ->send();
            return redirect()->back();
        }

        $callbackUrl = url('/payment/paystack/callback');
        
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
                'amount' => $finalAmount * 100,
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
            return redirect()->back();
        }

        $result = json_decode($response, true);

        if ($result && $result['status'] && isset($result['data']['authorization_url'])) {
            return redirect()->away($result['data']['authorization_url']);
        } else {
            Notification::make()
                ->danger()
                ->title('Payment Error')
                ->body($result['message'] ?? 'Failed to initialize payment.')
                ->send();
            return redirect()->back();
        }
    }
    
    protected function redirectToFlutterwaveFromAction($transaction, $gateway, $finalAmount): \Illuminate\Http\RedirectResponse
    {
        if (!$gateway->public_key || !$gateway->secret_key) {
            Notification::make()
                ->danger()
                ->title('Payment Gateway Error')
                ->body('Flutterwave is not properly configured. Please contact support.')
                ->send();
            return redirect()->back();
        }

        $callbackUrl = url('/payment/flutterwave/callback');
        
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
                'amount' => $finalAmount,
                'currency' => 'NGN',
                'redirect_url' => $callbackUrl,
                'customer' => [
                    'email' => Auth::user()->email,
                    'name' => Auth::user()->name,
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
            return redirect()->back();
        }

        $result = json_decode($response, true);

        if ($result && $result['status'] === 'success' && isset($result['data']['link'])) {
            return redirect()->away($result['data']['link']);
        } else {
            Notification::make()
                ->danger()
                ->title('Payment Error')
                ->body($result['message'] ?? 'Failed to initialize payment.')
                ->send();
            return redirect()->back();
        }
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
                                $this->couponError = null;
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
        $this->couponError = null;
        
        if (empty($couponCode)) {
            $this->appliedCoupon = null;
            $this->discountAmount = 0;
            $this->updateFinalAmount();
            return;
        }
        
        $coupon = Coupon::where('code', strtoupper($couponCode))->first();
        
        if (!$coupon) {
            $this->couponError = 'The coupon code you entered is invalid.';
            $this->appliedCoupon = null;
            $this->discountAmount = 0;
            $this->updateFinalAmount();
            return;
        }
        
        // Check if coupon applies to subscriptions
        if (!in_array($coupon->applies_to, ['all', 'subscriptions'])) {
            $this->couponError = 'This coupon cannot be used for subscriptions.';
            $this->appliedCoupon = null;
            $this->discountAmount = 0;
            $this->updateFinalAmount();
            return;
        }
        
        // Check if coupon applies to this specific plan
        if ($coupon->applies_to === 'subscriptions' && $coupon->applicable_plans) {
            if (!in_array($this->selectedPlanId, $coupon->applicable_plans)) {
                $this->couponError = 'This coupon cannot be used for the selected plan.';
                $this->appliedCoupon = null;
                $this->discountAmount = 0;
                $this->updateFinalAmount();
                return;
            }
        }
        
        // Check if coupon is valid
        if (!$coupon->isValid()) {
            $this->couponError = 'This coupon is no longer valid or has expired.';
            $this->appliedCoupon = null;
            $this->discountAmount = 0;
            $this->updateFinalAmount();
            return;
        }
        
        // Check minimum purchase amount
        $basePrice = $this->getCurrentPlanPrice();
        if ($coupon->min_purchase_amount && $basePrice < $coupon->min_purchase_amount) {
            $this->couponError = 'This coupon requires a minimum purchase of ₦' . number_format($coupon->min_purchase_amount, 2) . '.';
            $this->appliedCoupon = null;
            $this->discountAmount = 0;
            $this->updateFinalAmount();
            return;
        }
        
        // Check if user can use this coupon
        if (!$coupon->canBeUsedBy(Auth::id())) {
            $this->couponError = 'You have reached the usage limit for this coupon.';
            $this->appliedCoupon = null;
            $this->discountAmount = 0;
            $this->updateFinalAmount();
            return;
        }
        
        // Clear any previous errors and apply coupon
        $this->couponError = null;
        
        $basePrice = $this->getCurrentPlanPrice();
        $this->appliedCoupon = $coupon;
        $this->discountAmount = $coupon->calculateDiscount($basePrice);
        $this->updateFinalAmount();
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
    
    protected function redirectToPaystack($transaction, $gateway)
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
            return redirect()->away($result['data']['authorization_url']);
        } else {
            Notification::make()
                ->danger()
                ->title('Payment Error')
                ->body($result['message'] ?? 'Failed to initialize payment.')
                ->send();
        }
    }
    
    protected function redirectToFlutterwave($transaction, $gateway)
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
            return redirect()->away($result['data']['link']);
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
    
    protected function processWalletPayment($transaction, $gateway, $plan, $finalAmount = null): void
    {
        $user = Auth::user();
        $wallet = $user->wallet;
        $amount = $finalAmount ?? $this->finalAmount;
        
        if ($wallet->balance < $amount) {
            Notification::make()
                ->danger()
                ->title('Insufficient Balance')
                ->body('Your wallet balance is insufficient. Please add funds first.')
                ->send();
            return;
        }
        
        // Deduct from wallet
        $wallet->withdraw(
            $amount,
            'Subscription payment for ' . $plan->name,
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
