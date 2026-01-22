<?php
// ============================================
// SUBSCRIPTION PAGE
// app/Filament/Business/Pages/SubscriptionPage.php
// ============================================

namespace App\Filament\Business\Pages;

use App\Models\PaymentGateway;
use App\Models\Coupon;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\PaymentService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SubscriptionPage extends Page implements HasForms, HasActions
{
    use InteractsWithForms, InteractsWithActions;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    
    protected static ?string $navigationLabel = 'Subscription';
    
    protected static ?string $navigationGroup = 'Billing & Marketing';
    
    protected static ?int $navigationSort = 7;

    protected static string $view = 'filament.business.pages.subscription-page';
    
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
        return SubscriptionPlan::where('is_active', true)
            ->orderBy('order')
            ->get();
    }
    
    public function openSubscriptionModal(int $planId): void
    {
        $this->mountAction('subscribe', ['planId' => $planId]);
    }
    
    public function subscribeAction(): Action
    {
        return Action::make('subscribe')
            ->label('Subscribe Now')
            ->icon('heroicon-o-credit-card')
            ->modalWidth('3xl')
            ->fillForm(function (array $arguments): array {
                return [
                    'plan_id' => $arguments['planId'] ?? null,
                ];
            })
            ->modalHeading(function (array $arguments) {
                $planId = $arguments['planId'] ?? null;
                if (!$planId) {
                    return 'Subscribe to Plan';
                }
                
                $plan = SubscriptionPlan::find($planId);
                if (!$plan) {
                    return 'Subscribe to Plan';
                }
                
                return new \Illuminate\Support\HtmlString(
                    view('filament.business.pages.subscription-modal-heading', ['plan' => $plan])->render()
                );
            })
            ->form(function (array $arguments) {
                $planId = $arguments['planId'] ?? null;
                if (!$planId) {
                    return [];
                }
                
                $plan = SubscriptionPlan::find($planId);
                if (!$plan) {
                    return [];
                }
                
                return [
                    Forms\Components\Hidden::make('plan_id')
                        ->default($plan->id),
                    
                    ...$this->getPaymentFormSchema($plan),
                ];
            })
            ->action(function (array $data) {
                $planId = $data['plan_id'] ?? null;
                if (!$planId) {
                    Notification::make()
                        ->danger()
                        ->title('Error')
                        ->body('Invalid plan selected.')
                        ->send();
                    return;
                }
                
                $plan = SubscriptionPlan::find($planId);
                if (!$plan) {
                    Notification::make()
                        ->danger()
                        ->title('Error')
                        ->body('Plan not found.')
                        ->send();
                    return;
                }
                
                return $this->processPayment($plan, $data);
            })
            ->requiresConfirmation(false);
    }
    
    // Old method - keeping for reference but not used anymore
    public function getSubscribeAction(int $planId): Action
    {
        $plan = SubscriptionPlan::where('id', $planId)
            ->where('is_active', true)
            ->firstOrFail();
        
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
                return $this->processPayment($plan, $data);
            })
            ->requiresConfirmation(false);
    }
    
    protected function getPaymentFormSchema($plan): array
    {
        return [
            Forms\Components\Section::make('Billing Period')
                ->schema([
                    Forms\Components\Select::make('billing_interval')
                        ->label('Billing Period')
                        ->options([
                            'monthly' => 'Monthly',
                            'yearly' => 'Yearly',
                        ])
                        ->default('monthly')
                        ->required()
                        ->native(false)
                        ->live(),
                ])
                ->visible(fn () => $plan->yearly_price !== null),
            
            Forms\Components\Section::make('Coupon Code (Optional)')
                ->schema([
                    Forms\Components\TextInput::make('coupon_code')
                        ->label('Coupon Code')
                        ->placeholder('Enter coupon code')
                        ->maxLength(50)
                        ->live(onBlur: true),
                    Forms\Components\Placeholder::make('coupon_message')
                        ->label('')
                        ->content(function (Forms\Get $get) use ($plan) {
                            $couponCode = $get('coupon_code');
                            if (empty($couponCode)) {
                                return null;
                            }
                            
                            $billingInterval = $get('billing_interval') ?? 'monthly';
                            $basePrice = $this->calculateBasePrice($plan, $billingInterval);
                            
                            $coupon = Coupon::where('code', strtoupper(trim($couponCode)))->first();
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
                            
                            if (!$this->isCouponApplicable($coupon, $plan->id, $basePrice)) {
                                return new \Illuminate\Support\HtmlString(
                                    '<div class="text-danger-600 dark:text-danger-400 text-sm">This coupon cannot be used for this plan.</div>'
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
                            $basePrice = $this->calculateBasePrice($plan, $billingInterval);
                            
                            $couponCode = $get('coupon_code');
                            $discount = 0;
                            if (!empty($couponCode)) {
                                $coupon = Coupon::where('code', strtoupper(trim($couponCode)))->first();
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
    
    protected function calculateBasePrice($plan, string $billingInterval): float
    {
        return $billingInterval === 'yearly' && $plan->yearly_price 
            ? (float) $plan->yearly_price 
            : (float) $plan->price;
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
    
    protected function processPayment($plan, array $data): mixed
    {
        try {
            $user = Auth::user();
            $billingInterval = $data['billing_interval'] ?? 'monthly';
            
            // Check for existing active subscription
            $existingSubscription = $user->subscription()
                ->whereIn('status', ['active', 'pending'])
                ->first();
            
            if ($existingSubscription && $existingSubscription->status === 'active') {
                Notification::make()
                    ->warning()
                    ->title('Existing Subscription')
                    ->body('You already have an active subscription. Please cancel it first before subscribing to a new plan.')
                    ->send();
                return null;
            }
            
            // Calculate prices
            $basePrice = $this->calculateBasePrice($plan, $billingInterval);
            $discount = 0;
            $coupon = null;
            
            // Validate and apply coupon if provided
            if (!empty($data['coupon_code'])) {
                $coupon = Coupon::where('code', strtoupper(trim($data['coupon_code'])))->first();
                if ($coupon && $coupon->isValid() && $this->isCouponApplicable($coupon, $plan->id, $basePrice)) {
                    $discount = $coupon->calculateDiscount($basePrice);
                }
            }
            
            $finalAmount = max(0, $basePrice - $discount);
            
            // Use database transaction
            DB::beginTransaction();
            
            try {
                // Create subscription
                $duration = $billingInterval === 'yearly' ? 12 : 1;
                $subscription = Subscription::create([
                    'user_id' => $user->id,
                    'subscription_plan_id' => $plan->id,
                    'status' => 'pending',
                    'starts_at' => now(),
                    'ends_at' => now()->addMonths($duration),
                    'payment_method' => PaymentGateway::find($data['payment_gateway_id'])->slug,
                    'auto_renew' => true,
                ]);
                
                // Initialize payment through service
                $result = app(PaymentService::class)->initializePayment(
                    user: $user,
                    amount: $finalAmount,
                    gatewayId: $data['payment_gateway_id'],
                    payable: $subscription,
                    metadata: [
                        'plan_id' => $plan->id,
                        'billing_interval' => $billingInterval,
                        'original_amount' => $basePrice,
                        'discount_amount' => $discount,
                        'coupon_code' => $coupon?->code,
                    ]
                );
                
                // Apply coupon if valid
                if ($coupon && isset($result) && $result->success) {
                    try {
                        // We need the transaction ID, but it's created inside the service
                        // So we'll get the latest transaction for this subscription
                        $transaction = $subscription->transactions()->latest()->first();
                        if ($transaction) {
                            $coupon->apply($user->id, $basePrice, $transaction->id);
                        }
                    } catch (\Exception $e) {
                        Log::warning('Coupon application failed', [
                            'user_id' => $user->id,
                            'coupon_id' => $coupon->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                
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
                        ->title('Payment Successful!')
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
            Log::error('Subscription payment failed', [
                'user_id' => Auth::id(),
                'plan_id' => $plan->id,
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
}
