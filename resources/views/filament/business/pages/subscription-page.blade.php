<x-filament-panels::page>
    @if(session('success'))
        <div class="mb-4 rounded-lg bg-success-50 dark:bg-success-900/20 border border-success-200 dark:border-success-800 p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-success-600 dark:text-success-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-sm font-medium text-success-800 dark:text-success-200">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif
    
    @if(session('error'))
        <div class="mb-4 rounded-lg bg-danger-50 dark:bg-danger-900/20 border border-danger-200 dark:border-danger-800 p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-danger-600 dark:text-danger-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-sm font-medium text-danger-800 dark:text-danger-200">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif
    
    @php
        $currentSubscription = $this->getCurrentSubscription();
        $activeBusiness = app(\App\Services\ActiveBusiness::class)->getActiveBusiness();
    @endphp
    
    {{-- Current Subscription Banner --}}
    @if($currentSubscription && $activeBusiness)
        <x-filament::section class="mb-6">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Current Subscription for {{ $activeBusiness->business_name }}
                    </h3>
                    <div class="mt-2 flex items-center gap-4">
                        <div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Plan:</span>
                            <span class="ml-1 font-medium text-gray-900 dark:text-white">{{ $currentSubscription->plan->name }}</span>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Status:</span>
                            <x-filament::badge color="success" class="ml-1">
                                {{ ucfirst($currentSubscription->status) }}
                            </x-filament::badge>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Expires:</span>
                            <span class="ml-1 font-medium {{ $currentSubscription->daysRemaining() <= 7 ? 'text-danger-600' : 'text-gray-900 dark:text-white' }}">
                                {{ $currentSubscription->ends_at->format('M j, Y') }} ({{ $currentSubscription->daysRemaining() }} days left)
                            </span>
                        </div>
                    </div>
                    
                    {{-- Plan Limits --}}
                    <div class="mt-4 grid grid-cols-2 md:grid-cols-5 gap-4">
                        <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div class="text-2xl font-bold text-primary-600">{{ $currentSubscription->plan->max_faqs ?? '∞' }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">FAQs</div>
                        </div>
                        <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div class="text-2xl font-bold text-primary-600">{{ $currentSubscription->plan->max_leads_view ?? '∞' }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">View Leads</div>
                        </div>
                        <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div class="text-2xl font-bold text-primary-600">{{ $currentSubscription->plan->max_products ?? '∞' }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Products</div>
                        </div>
                        <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div class="text-2xl font-bold text-primary-600">{{ $currentSubscription->plan->max_photos ?? '∞' }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Photos</div>
                        </div>
                        <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div class="text-2xl font-bold text-warning-600">{{ $currentSubscription->plan->monthly_ad_credits ?? '0' }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Ad Credits/mo</div>
                        </div>
                    </div>
                </div>
                
                <div class="ml-6">
                    <x-filament::button 
                        href="{{ route('filament.business.resources.subscriptions.view', ['record' => $currentSubscription->id]) }}"
                        color="gray"
                        outlined
                    >
                        View Details
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>
    @endif
    
    <div class="space-y-6">
        @php
            $plans = $this->getAllPlans();
        @endphp
        
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
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-yellow-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                                </svg>
                                <span class="text-sm font-semibold text-yellow-700 dark:text-yellow-400">{{ $plan->monthly_ad_credits ?? '0' }} Ad Credits/month</span>
                            </li>
                        </ul>
                        
                        <x-filament::button 
                            wire:click="openSubscriptionModal({{ $plan->id }})"
                            class="w-full mt-6"
                            color="{{ $plan->is_popular ? 'primary' : 'gray' }}"
                            size="lg"
                        >
                            <x-slot name="icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                                </svg>
                            </x-slot>
                            Subscribe Now
                        </x-filament::button>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    </div>
    
    <x-filament-actions::modals />
</x-filament-panels::page>