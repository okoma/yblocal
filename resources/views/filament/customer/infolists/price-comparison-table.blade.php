@php
    $shortlistedQuotes = $getRecord()->responses()->where('status', 'shortlisted')->orderBy('price', 'asc')->get();
@endphp

@if($shortlistedQuotes->count() > 1)
    <div class="overflow-x-auto">
        <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
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
                                href="#shortlisted"
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
    
    <div class="mt-4 p-4 bg-primary-50 dark:bg-primary-900/20 rounded-lg">
        <div class="flex items-start">
            <svg class="h-5 w-5 text-primary-600 dark:text-primary-400 mt-0.5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="text-sm text-primary-700 dark:text-primary-300">
                <strong>Tip:</strong> Compare prices, delivery times, and messages to make the best decision. Quotes are sorted by price (lowest first).
            </div>
        </div>
    </div>
@else
    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
        <p>You need at least 2 shortlisted quotes to compare prices.</p>
    </div>
@endif
