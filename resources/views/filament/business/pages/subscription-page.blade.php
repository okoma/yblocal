<x-filament-panels::page>
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
                            >
                                Subscribe Now
                            </x-filament::button>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    </div>
    
    @if($this->selectedPlanId)
        @php
            $plan = \App\Models\SubscriptionPlan::find($this->selectedPlanId);
        @endphp
        
        <x-filament::modal id="subscribe-modal" width="md">
            <x-slot name="heading">
                Subscribe to {{ $plan->name }}
            </x-slot>
            
            <x-slot name="description">
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span>Plan Price:</span>
                        <span class="font-semibold">₦{{ number_format($plan->price, 2) }}</span>
                    </div>
                    @if($this->appliedCoupon && $this->discountAmount > 0)
                        <div class="flex justify-between text-success-600 dark:text-success-400">
                            <span>Discount ({{ $this->appliedCoupon->code }}):</span>
                            <span class="font-semibold">-₦{{ number_format($this->discountAmount, 2) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between border-t pt-2 mt-2">
                        <span class="font-bold">Total Amount:</span>
                        <span class="font-bold text-lg">₦{{ number_format($this->finalAmount, 2) }}</span>
                    </div>
                </div>
            </x-slot>
            
            <form wire:submit="processPayment">
                {{ $this->paymentForm }}
                
                @if($this->appliedCoupon)
                    <div class="mt-4 p-3 bg-success-50 dark:bg-success-900/20 rounded-lg">
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
                
                <div class="mt-6 flex justify-end gap-3">
                    <x-filament::button 
                        type="button"
                        color="gray" 
                        wire:click="$set('selectedPlanId', null)"
                    >
                        Cancel
                    </x-filament::button>
                    <x-filament::button type="submit" color="primary">
                        Pay ₦{{ number_format($this->finalAmount, 2) }}
                    </x-filament::button>
                </div>
            </form>
        </x-filament::modal>
    @endif
</x-filament-panels::page>