<x-filament-panels::page>
    @php
        $shortlistedQuotes = $this->getShortlistedQuotes();
    @endphp

    @if($shortlistedQuotes->count() > 1)
        <div class="space-y-6">
            <x-filament::section>
                <x-slot name="heading">
                    Price Comparison
                </x-slot>
                <x-slot name="description">
                    Compare shortlisted quotes side by side. Quotes are sorted by price (lowest first).
                </x-slot>

                <div class="overflow-x-auto">
                    <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Quote Request</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Business</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Price</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Delivery Time</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Message</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($shortlistedQuotes as $quote)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            <a href="{{ \App\Filament\Customer\Resources\QuoteRequestResource::getUrl('view', ['record' => $quote->quoteRequest->id]) }}" class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300">
                                                {{ $quote->quoteRequest->title }}
                                            </a>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $quote->business->business_name ?? 'N/A' }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-success-600 dark:text-success-400">
                                            ₦{{ number_format($quote->price, 2) }}
                                        </div>
                                        @if($loop->first)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-success-100 text-success-800 dark:bg-success-900 dark:text-success-200">
                                                Lowest Price
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $quote->delivery_time ?? 'Not specified' }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="text-sm text-gray-500 dark:text-gray-400 max-w-xs truncate">
                                            {{ $quote->message ?? 'No message' }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                                        <a 
                                            href="{{ \App\Filament\Customer\Resources\QuoteRequestResource::getUrl('view', ['record' => $quote->quoteRequest->id]) }}#shortlisted"
                                            class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300 underline"
                                        >
                                            View Details
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
            
            <div class="p-4 bg-primary-50 dark:bg-primary-900/20 rounded-lg">
                <div class="flex items-start">
                    <svg class="h-5 w-5 text-primary-600 dark:text-primary-400 mt-0.5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="text-sm text-primary-700 dark:text-primary-300">
                        <strong>Tip:</strong> Compare prices, delivery times, and messages to make the best decision. Quotes are sorted by price (lowest first). Click "View Details" to see full quote information and take actions.
                    </div>
                </div>
            </div>
        </div>
    @else
        <x-filament::section>
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No quotes to compare</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    You need at least 2 shortlisted quotes to compare prices. 
                    <a href="{{ \App\Filament\Customer\Resources\QuoteRequestResource::getUrl('index') }}" class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300 underline">
                        Go to Quote Requests
                    </a> to shortlist quotes.
                </p>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
