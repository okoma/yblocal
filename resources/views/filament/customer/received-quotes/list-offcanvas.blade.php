@php
    $items = $items ?? collect();
    $type = $type ?? 'accepted';
@endphp

<div class="space-y-4">
    @if($items->isEmpty())
        <p class="text-sm text-gray-500 dark:text-gray-400">No {{ $type }} quotes.</p>
    @else
        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
            @foreach($items as $quote)
                <li class="py-4 first:pt-0">
                    <div class="flex flex-col gap-2">
                        <div class="flex items-start justify-between gap-2">
                            <a href="{{ \App\Filament\Customer\Resources\QuoteRequestResource::getUrl('view', ['record' => $quote->quote_request_id]) }}" class="text-sm font-medium text-primary-600 dark:text-primary-400 hover:underline">
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
