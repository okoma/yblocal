@php
    $sortedQuotes = $quotes->sortBy('price');
@endphp

<div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <h5 class="text-sm font-semibold text-gray-900 dark:text-white">Price Comparison</h5>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Business</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Price</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Delivery Time</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Message</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($sortedQuotes as $quote)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="px-4 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $quote->business->business_name ?? 'N/A' }}
                            </div>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap">
                            <div class="text-sm font-bold text-green-600 dark:text-green-400">
                                ₦{{ number_format($quote->price, 2) }}
                            </div>
                            @if($loop->first)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
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
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="px-4 py-3 bg-blue-50 dark:bg-blue-900/20">
        <div class="flex items-start gap-2">
            <x-heroicon-o-information-circle class="h-5 w-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5"/>
            <div class="text-sm text-blue-700 dark:text-blue-300">
                <strong>Tip:</strong> Compare prices, delivery times, and messages to make the best decision. Quotes are sorted by price (lowest first).
            </div>
        </div>
    </div>
</div>