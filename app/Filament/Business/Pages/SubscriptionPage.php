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
        $this->paymentData = [
            'payment_gateway_id' => null,
            'coupon_code' => null,
        ];
        $this->appliedCoupon = null;
        $this->discountAmount = 0;
        $this->finalAmount = $plan->price;
        $this->form->fill($this->paymentData);
        $this->dispatch('open-modal', id: 'subscribe-modal');
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Payment Details')
                    ->schema([
                        Forms\Components\Select::make('payment_gateway_id')
                            ->label('Payment Method')
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
                                }
                            }),
                    ])
                    ->collapsible()
                    ->collapsed(),
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
        $plan = \App\Models\SubscriptionPlan::findOrFail($this->selectedPlanId);
        $this->appliedCoupon = $coupon;
        $this->discountAmount = $coupon->calculateDiscount($plan->price);
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
        $this->finalAmount = max(0, $plan->price - $this->discountAmount);
    }
    
    public function processPayment(): void
    {
        $data = $this->form->getState();
        $plan = \App\Models\SubscriptionPlan::findOrFail($this->selectedPlanId);
        $gateway = PaymentGateway::findOrFail($data['payment_gateway_id']);
        $user = Auth::user();
        
        // Create pending subscription
        $subscription = \App\Models\Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'pending',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
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
            'description' => 'Subscription payment for ' . $plan->name,
            'metadata' => [
                'original_amount' => $plan->price,
                'discount_amount' => $this->discountAmount,
                'coupon_code' => $this->appliedCoupon?->code,
                'coupon_id' => $this->appliedCoupon?->id,
            ],
        ]);
        
        // Apply coupon if one was used
        if ($this->appliedCoupon) {
            try {
                $this->appliedCoupon->apply($user->id, $plan->price, $transaction->id);
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
        // TODO: Implement Paystack payment redirect
        Notification::make()
            ->info()
            ->title('Paystack Integration')
            ->body('Paystack payment integration will be implemented here.')
            ->send();
    }
    
    protected function redirectToFlutterwave($transaction, $gateway): void
    {
        // TODO: Implement Flutterwave payment redirect
        Notification::make()
            ->info()
            ->title('Flutterwave Integration')
            ->body('Flutterwave payment integration will be implemented here.')
            ->send();
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
