<x-filament-panels::page>
    {{-- Credit Balance Card --}}
    @php
        $businessId = app(\App\Services\ActiveBusiness::class)->getActiveBusinessId();
        $wallet = $businessId ? \App\Models\Wallet::where('business_id', $businessId)->first() : null;
        $credits = $wallet ? $wallet->quote_credits : 0;
    @endphp
    
    <div class="mb-6">
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-3">
                        <div class="rounded-lg p-3 {{ $credits > 0 ? 'bg-success-100 dark:bg-success-900/20' : 'bg-gray-100 dark:bg-gray-900/20' }}">
                            <x-heroicon-o-wallet class="h-6 w-6 {{ $credits > 0 ? 'text-success-600 dark:text-success-400' : 'text-gray-600 dark:text-gray-400' }}" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                Quote Credits
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Available for quote submissions
                            </p>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <div class="text-3xl font-bold {{ $credits > 0 ? 'text-success-600 dark:text-success-400' : 'text-gray-600 dark:text-gray-400' }}">
                            {{ $credits }}
                            <span class="text-lg font-normal text-gray-500 dark:text-gray-400">
                                {{ $credits === 1 ? 'credit' : 'credits' }}
                            </span>
                        </div>
                        
                        @if($credits > 0)
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                Each quote submission costs 1 credit. You can submit {{ $credits }} more {{ $credits === 1 ? 'quote' : 'quotes' }}.
                            </p>
                        @else
                            <div class="mt-3 flex items-start gap-2 rounded-lg bg-warning-50 dark:bg-warning-900/10 p-3 border border-warning-200 dark:border-warning-900/20">
                                <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-warning-600 dark:text-warning-400 flex-shrink-0 mt-0.5" />
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-warning-800 dark:text-warning-300">
                                        No credits available
                                    </p>
                                    <p class="mt-1 text-sm text-warning-700 dark:text-warning-400">
                                        You need quote credits to submit quotes to customers. Purchase credits to start bidding on projects.
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                
                <div class="ml-6">
                    <a 
                        href="{{ \App\Filament\Business\Resources\WalletResource::getUrl('index', panel: 'business') }}"
                        class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition"
                    >
                        <x-heroicon-o-plus class="h-4 w-4" />
                        Purchase Credits
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    {{-- How It Works Card --}}
    <div class="mb-6">
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gradient-to-br from-primary-50 to-white dark:from-primary-900/10 dark:to-gray-800 p-6">
            <div class="flex items-start gap-4">
                <div class="rounded-lg p-2 bg-primary-100 dark:bg-primary-900/20">
                    <x-heroicon-o-light-bulb class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                </div>
                <div class="flex-1">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3">
                        How It Works
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 flex items-center justify-center w-6 h-6 rounded-full bg-primary-600 text-white text-xs font-bold">
                                1
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Browse Requests</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">View all available quote requests matching your category and location</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 flex items-center justify-center w-6 h-6 rounded-full bg-primary-600 text-white text-xs font-bold">
                                2
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Submit Your Quote</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Enter your price, delivery time, and proposal message</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 flex items-center justify-center w-6 h-6 rounded-full bg-primary-600 text-white text-xs font-bold">
                                3
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Win Projects</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Customer reviews quotes and selects the best fit</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Table --}}
    {{ $this->table }}
</x-filament-panels::page>