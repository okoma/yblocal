<x-filament-panels::page>
    @if(session('success'))
        <x-filament::notification
            type="success"
            :title="session('success')"
            :close-button="true"
        />
    @endif
    
    @if(session('error'))
        <x-filament::notification
            type="danger"
            :title="session('error')"
            :close-button="true"
        />
    @endif
    
    <div class="space-y-6">
        @php
            $subscription = $this->getCurrentSubscription();
            $plans = $this->getAllPlans();
        @endphp
        
        @if($subscription)
            <x-filament::section>
                <x-slot name="heading">
                    Current Subscription
                </x-slot>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Plan</h4>
                        <p class="mt-1 text-lg font-semibold">{{ $subscription->plan->name }}</p>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</h4>
                        <x-filament::badge 
                            :color="$subscription->status === 'active' ? 'success' : 'danger'"
                            class="mt-1"
                        >
                            {{ ucfirst($subscription->status) }}
                        </x-filament::badge>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Expires</h4>
                        <p class="mt-1 text-lg">{{ $subscription->ends_at->format('M d, Y') }}</p>
                        <p class="text-sm text-gray-500">{{ $subscription->daysRemaining() }} days left</p>
                    </div>
                </div>
                
                <div class="mt-6 space-y-4">
                    <h4 class="font-medium">Usage Statistics</h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div class="text-sm text-gray-500 dark:text-gray-400">FAQs</div>
                            <div class="text-2xl font-bold mt-1">
                                {{ $subscription->faqs_used }} / {{ $subscription->plan->max_faqs ?? '∞' }}
                            </div>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Leads Viewed</div>
                            <div class="text-2xl font-bold mt-1">
                                {{ $subscription->leads_viewed_used }} / {{ $subscription->plan->max_leads_view ?? '∞' }}
                            </div>
                            <div class="text-xs text-gray-400 mt-1">Unlimited receiving</div>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Products</div>
                            <div class="text-2xl font-bold mt-1">
                                {{ $subscription->products_used }} / {{ $subscription->plan->max_products ?? '∞' }}
                            </div>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Photos</div>
                            <div class="text-2xl font-bold mt-1">
                                {{ $subscription->photos_used }} / {{ $subscription->plan->max_photos ?? '∞' }}
                            </div>
                        </div>
                    </div>
                </div>
            </x-filament::section>
        @endif
        
        <x-filament::section>
            <x-slot name="heading">
                Available Plans
            </x-slot>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach($plans as $plan)
                    <div class="border rounded-lg p-6 {{ $plan->is_popular ? 'border-primary-500 ring-2 ring-primary-500' : 'border-gray-200 dark:border-gray-700' }}">
                        @if($plan->is_popular)
                            <x-filament::badge color="primary" class="mb-2">
                                Most Popular
                            </x-filament::badge>
                        @endif
                        
                        <h3 class="text-xl font-bold">{{ $plan->name }}</h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            {{ $plan->description }}
                        </p>
                        
                        <div class="mt-4">
                            <span class="text-3xl font-bold">₦{{ number_format($plan->price, 2) }}</span>
                            <span class="text-gray-500">/month</span>
                        </div>
                        
                        <ul class="mt-6 space-y-3">
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-sm">{{ $plan->max_faqs ?? 'Unlimited' }} FAQs</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                <span class="text-sm">View {{ $plan->max_leads_view ?? 'Unlimited' }} Leads</span>
                                <span class="text-xs text-gray-400 ml-1">(Unlimited receiving)</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-sm">{{ $plan->max_products ?? 'Unlimited' }} Products</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-sm">{{ $plan->max_photos ?? 'Unlimited' }} Photos</span>
                            </li>
                        </ul>
                        
                        @if($subscription && $subscription->plan_id === $plan->id)
                            <x-filament::button 
                                class="w-full mt-6"
                                color="gray"
                                disabled
                            >
                                Current Plan
                            </x-filament::button>
                        @else
                            <x-filament::button 
                                class="w-full mt-6"
                                :color="$plan->is_popular ? 'primary' : 'gray'"
                                wire:click="openPaymentModal({{ $plan->id }})"
                                x-on:click="$nextTick(() => $dispatch('open-modal', { id: 'subscribe-modal' }))"
                            >
                                Subscribe Now
                            </x-filament::button>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    </div>
    
    <x-filament::modal 
        id="subscribe-modal" 
        width="3xl"
        :close-by-clicking-away="true"
    >
        @if($this->selectedPlanId)
            @php
                $plan = \App\Models\SubscriptionPlan::find($this->selectedPlanId);
            @endphp
            <x-slot name="heading">
                <div class="text-center">
                    <div class="flex justify-center mb-3">
                        <div class="w-16 h-16 bg-primary-100 dark:bg-primary-900 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold">{{ $plan->name }}</h3>
                </div>
            </x-slot>
            
            <form wire:submit="processPayment">
                {{-- Billing Interval Toggle --}}
                <div class="mb-6">
                    <div class="flex items-center justify-center gap-4">
                        <span class="text-sm font-medium {{ $this->billingInterval === 'monthly' ? 'text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400' }}">
                            Monthly
                        </span>
                        <button 
                            type="button"
                            class="relative inline-flex items-center cursor-pointer focus:outline-none"
                            wire:click="$set('billingInterval', $billingInterval === 'monthly' ? 'yearly' : 'monthly')"
                            wire:loading.attr="disabled"
                        >
                            <input 
                                type="checkbox" 
                                class="sr-only peer" 
                                @checked($this->billingInterval === 'yearly')
                                readonly
                                tabindex="-1"
                            >
                            <div class="w-11 h-6 bg-gray-200 rounded-full peer dark:bg-gray-700 {{ $this->billingInterval === 'yearly' ? 'bg-primary-600' : '' }} relative after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 {{ $this->billingInterval === 'yearly' ? 'after:translate-x-full' : '' }}"></div>
                        </button>
                        <span class="text-sm font-medium {{ $this->billingInterval === 'yearly' ? 'text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400' }}">
                            Yearly
                        </span>
                    </div>
                    @if($this->billingInterval === 'yearly' && $plan->yearly_price)
                        @php
                            $monthlyTotal = $plan->price * 12;
                            $savings = $monthlyTotal - $plan->yearly_price;
                            $savingsPercent = round(($savings / $monthlyTotal) * 100);
                        @endphp
                        <p class="text-center text-sm text-success-600 dark:text-success-400 mt-2">
                            Save {{ $savingsPercent }}% (₦{{ number_format($savings, 2) }}) with yearly billing
                        </p>
                    @endif
                </div>
                
                {{-- Form Fields (Coupon and Payment Method) --}}
                <div class="mb-6 space-y-4">
                    {{ $this->form }}
                </div>
                
                @if($this->appliedCoupon)
                    <div class="mb-6 p-3 bg-success-50 dark:bg-success-900/20 rounded-lg">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-success-600 dark:text-success-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-success-800 dark:text-success-200">
                                    Coupon Applied: {{ $this->appliedCoupon->code }}
                                </p>
                                @if($this->appliedCoupon->description)
                                    <p class="text-xs text-success-600 dark:text-success-400">
                                        {{ $this->appliedCoupon->description }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
                
                {{-- Summary Section --}}
                <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                    <h4 class="font-semibold mb-3">Order Summary</h4>
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span>Plan:</span>
                            <span class="font-medium">{{ $plan->name }} ({{ ucfirst($this->billingInterval) }})</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span>Plan Price:</span>
                            <span class="font-medium">₦{{ number_format($this->getCurrentPlanPrice(), 2) }}</span>
                        </div>
                        @if($this->appliedCoupon && $this->discountAmount > 0)
                            <div class="flex justify-between text-sm text-success-600 dark:text-success-400">
                                <span>Discount ({{ $this->appliedCoupon->code }}):</span>
                                <span class="font-medium">-₦{{ number_format($this->discountAmount, 2) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between border-t border-gray-200 dark:border-gray-700 pt-2 mt-2">
                            <span class="font-bold">Total Amount:</span>
                            <span class="font-bold text-lg text-primary-600 dark:text-primary-400">₦{{ number_format($this->finalAmount, 2) }}</span>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end gap-3">
                    <x-filament::button 
                        type="button"
                        color="gray" 
                        wire:click="$set('selectedPlanId', null)"
                    >
                        Cancel
                    </x-filament::button>
                    <x-filament::button 
                        type="submit" 
                        color="primary" 
                        size="lg"
                        wire:loading.attr="disabled"
                        wire:target="processPayment"
                    >
                        <span wire:loading.remove wire:target="processPayment">Pay ₦{{ number_format($this->finalAmount, 2) }}</span>
                        <span wire:loading wire:target="processPayment">Pay ₦{{ number_format($this->finalAmount, 2) }}</span>
                    </x-filament::button>
                </div>
            </form>
        @else
            <div class="p-4 text-center">
                <p class="text-gray-500">Loading...</p>
            </div>
        @endif
    </x-filament::modal>
</x-filament-panels::page>