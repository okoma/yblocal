@php
    $items = $items ?? collect();
    $showCompare = $showCompare ?? false;
@endphp

<div x-data="{ tab: 'list' }" class="space-y-4">
    @if($showCompare)
        <div class="flex gap-2 border-b border-gray-200 dark:border-gray-700 pb-2">
            <button type="button"
                    @click="tab = 'list'"
                    :class="tab === 'list' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 dark:text-gray-400'"
                    class="px-3 py-1.5 text-sm font-medium border-b-2 transition-colors">
                List
            </button>
            <button type="button"
                    @click="tab = 'compare'"
                    :class="tab === 'compare' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 dark:text-gray-400'"
                    class="px-3 py-1.5 text-sm font-medium border-b-2 transition-colors">
                Compare
            </button>
        </div>
    @endif

    <div x-show="tab === 'list'" x-transition class="space-y-4">
        @if($items->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">No shortlisted quotes.</p>
        @else
            <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($items as $quote)
                    <li class="py-4 first:pt-0">
                        <div class="flex flex-col gap-2">
                            <div class="flex items-start justify-between gap-2">
                                <a href="{{ \App\Filament\Customer\Resources\QuoteRequestResource::getUrl('view', ['record' => $quote->quote_request_id]) }}#shortlisted" class="text-sm font-medium text-primary-600 dark:text-primary-400 hover:underline">
                                    {{ $quote->quoteRequest->title ?? 'Quote Request' }}
                                </a>
                                <span class="text-sm font-bold text-success-600 dark:text-success-400">₦{{ number_format($quote->price, 2) }}</span>
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">{{ $quote->business->business_name ?? 'N/A' }}</div>
                            @if($quote->delivery_time)
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $quote->delivery_time }}</div>
                            @endif
                            @if($quote->message)
                                <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2">{{ $quote->message }}</p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    @if($showCompare)
        <div x-show="tab === 'compare'" x-transition class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Business</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Price</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Delivery</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Message</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($items as $index => $quote)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-3 py-2 font-medium text-gray-900 dark:text-white">{{ $quote->business->business_name ?? 'N/A' }}</td>
                            <td class="px-3 py-2">
                                <span class="font-bold text-success-600 dark:text-success-400">₦{{ number_format($quote->price, 2) }}</span>
                                @if($index === 0)
                                    <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-success-100 text-success-800 dark:bg-success-900 dark:text-success-200">Lowest</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-gray-500 dark:text-gray-400">{{ $quote->delivery_time ?? '—' }}</td>
                            <td class="px-3 py-2 text-gray-500 dark:text-gray-400 max-w-[200px] truncate">{{ $quote->message ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
