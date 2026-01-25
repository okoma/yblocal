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
use App\Services\ActiveBusiness;
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
        $business = app(ActiveBusiness::class)->getActiveBusiness();
        if (!$business) {
            return null;
        }
        return $business->subscriptions()
            ->with('plan')
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->first();
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
            ->modalSubmitActionLabel('Subscribe')
            ->modalFooterActionsAlignment('right')
            ->fillForm(function (array $arguments): array {
                return [
                    'plan_id' => $arguments['planId'] ?? null,
                    'billing_interval' => 'monthly',
                ];
            })
            ->modalHeading(fn () => 'Subscribe to Plan')
            ->modalDescription('Choose your business and billing period')
            ->modalIcon('heroicon-o-sparkles')
            ->form(function (array $arguments) {
                $planId = $arguments['planId'] ?? null;
                if (!$planId) {
                    return [];
                }
                
                $plan = SubscriptionPlan::find($planId);
                if (!$plan) {
                    return [];
                }
                
                $user = Auth::user();
                
                return [
                    // Plan Info Header
                    Forms\Components\Section::make()
                        ->schema([
                            Forms\Components\Placeholder::make('plan_info')
                                ->label('')
                                ->content(fn () => new \Illuminate\Support\HtmlString(
                                    '<div class="text-center py-4">
                                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary-100 dark:bg-primary-900 mb-3">
                                            <svg class="w-8 h-8 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        </div>
                                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">' . e($plan->name) . '</h3>
                                        <p class="text-gray-600 dark:text-gray-400 text-sm max-w-md mx-auto">' . e($plan->description) . '</p>
                                    </div>'
                                )),
                        ])
                        ->columnSpanFull(),
                    
                    Forms\Components\Hidden::make('plan_id')
                        ->default($plan->id),
                    
                    // Billing Period
                    Forms\Components\Section::make('Billing Period')
                        ->description('Choose how often you want to be billed')
                        ->icon('heroicon-o-calendar')
                        ->schema([
                            Forms\Components\Select::make('billing_interval')
                                ->label('Billing Period')
                                ->options(function () use ($plan) {
                                    $options = ['monthly' => 'Monthly'];
                                    
                                    if ($plan->yearly_price !== null && $plan->price > 0) {
                                        $monthlyTotal = $plan->price * 12;
                                        $yearlyPrice = $plan->yearly_price;
                                        $savings = $monthlyTotal - $yearlyPrice;
                                        $savingsPercent = round(($savings / $monthlyTotal) * 100);
                                        
                                        $options['yearly'] = 'Yearly (Save ' . $savingsPercent . '% - ₦' . number_format($savings, 2) . ')';
                                    }
                                    
                                    return $options;
                                })
                                ->default('monthly')
                                ->required()
                                ->native(false)
                                ->live()
                                ->selectablePlaceholder(false)
                                ->helperText(function () use ($plan) {
                                    if ($plan->yearly_price !== null && $plan->price > 0) {
                                        $monthlyTotal = $plan->price * 12;
                                        $yearlyPrice = $plan->yearly_price;
                                        $savings = $monthlyTotal - $yearlyPrice;
                                        $savingsPercent = round(($savings / $monthlyTotal) * 100);
                                        
                                        return new \Illuminate\Support\HtmlString(
                                            '<span class="text-success-600 dark:text-success-400 font-medium">Select yearly to save ' . $savingsPercent . '% (₦' . number_format($savings, 2) . ')</span>'
                                        );
                                    }
                                    return null;
                                }),
                        ])
                        ->columnSpanFull()
                        ->collapsed(false)
                        ->visible(fn () => $plan->yearly_price !== null),
                    
                    // Coupon Code
                    Forms\Components\Section::make('Coupon Code')
                        ->description('Have a discount code? Apply it here')
                        ->icon('heroicon-o-ticket')
                        ->collapsible()
                        ->collapsed()
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
                        ])
                        ->columnSpanFull(),
                    
                    // Business Selection (scoped to active business)
                    Forms\Components\Section::make('Select Business')
                        ->description('Choose which business this subscription is for')
                        ->icon('heroicon-o-building-office')
                        ->schema([
                            Forms\Components\Select::make('business_id')
                                ->label('Business')
                                ->options(function () {
                                    $active = app(ActiveBusiness::class);
                                    $b = $active->getActiveBusiness();
                                    return $b ? [$b->id => $b->business_name] : [];
                                })
                                ->default(fn () => app(ActiveBusiness::class)->getActiveBusinessId())
                                ->required()
                                ->searchable()
                                ->native(false)
                                ->placeholder('Select your business')
                                ->helperText('Subscriptions are assigned to individual businesses')
                                ->live()
                                ->afterStateUpdated(function ($state) {
                                    if ($state) {
                                        $business = \App\Models\Business::find($state);
                                        if ($business && $business->activeSubscription()) {
                                            \Filament\Notifications\Notification::make()
                                                ->warning()
                                                ->title('Existing Subscription')
                                                ->body('This business already has an active subscription.')
                                                ->send();
                                        }
                                    }
                                }),
                        ])
                        ->columnSpanFull()
                        ->collapsed(false),
                    
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
            Forms\Components\Section::make('Payment Method')
                ->description('Select your preferred payment method')
                ->icon('heroicon-o-credit-card')
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
                ->description('Review your subscription details')
                ->icon('heroicon-o-clipboard-document-list')
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
            
            // Validate business_id is provided
            if (empty($data['business_id'])) {
                Notification::make()
                    ->danger()
                    ->title('Business Required')
                    ->body('Please select a business for this subscription.')
                    ->send();
                return null;
            }
            
            // Get and verify the selected business
            $business = $user->businesses()->find($data['business_id']);
            
            if (!$business) {
                Notification::make()
                    ->danger()
                    ->title('Invalid Business')
                    ->body('The selected business was not found or you do not have access to it.')
                    ->send();
                return null;
            }
            
            // Check for existing active subscription for this business
            $existingSubscription = $business->activeSubscription();
            
            if ($existingSubscription) {
                Notification::make()
                    ->warning()
                    ->title('Existing Subscription')
                    ->body('This business already has an active subscription. Please cancel it first before subscribing to a new plan.')
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
                // Create subscription (subscriptions belong to businesses)
                $duration = $billingInterval === 'yearly' ? 12 : 1;
                $subscription = Subscription::create([
                    'business_id' => $business->id,
                    'user_id' => $user->id, // Who initiated the subscription
                    'subscription_plan_id' => $plan->id,
                    'billing_interval' => $billingInterval,
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
