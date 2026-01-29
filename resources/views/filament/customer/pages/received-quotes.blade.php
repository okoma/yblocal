<x-filament-panels::page>
    <div class="space-y-6">
        @php
            $quoteRequests = $this->getQuoteRequests();
        @endphp

        @if($quoteRequests->isEmpty())
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No quotes received yet</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">When businesses respond to your quote requests, they will appear here.</p>
            </div>
        @else
            @foreach($quoteRequests as $quoteRequest)
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                    {{-- Quote Request Header --}}
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ $quoteRequest->title }}
                                </h3>
                                <div class="mt-1 flex flex-wrap items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                                    <span class="inline-flex items-center gap-1">
                                        <x-heroicon-o-tag class="h-4 w-4"/>
                                        {{ $quoteRequest->category->name }}
                                    </span>
                                    <span class="inline-flex items-center gap-1">
                                        <x-heroicon-o-map-pin class="h-4 w-4"/>
                                        {{ $quoteRequest->stateLocation->name }}{{ $quoteRequest->cityLocation ? ', ' . $quoteRequest->cityLocation->name : '' }}
                                    </span>
                                    <span class="inline-flex items-center gap-1">
                                        <x-heroicon-o-inbox class="h-4 w-4"/>
                                        {{ $quoteRequest->responses->count() }} {{ Str::plural('quote', $quoteRequest->responses->count()) }}
                                    </span>
                                </div>
                            </div>
                            <div class="ml-4 flex-shrink-0">
                                <x-filament::badge :color="match($quoteRequest->status) {
                                    'open' => 'success',
                                    'closed' => 'gray',
                                    'expired' => 'warning',
                                    'accepted' => 'primary',
                                    default => 'gray',
                                }">
                                    {{ ucfirst($quoteRequest->status) }}
                                </x-filament::badge>
                            </div>
                        </div>
                    </div>

                    {{-- All Responses --}}
                    <div class="px-6 py-4">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">
                            All Responses ({{ $quoteRequest->responses->count() }})
                        </h4>
                        <div class="space-y-3">
                            @foreach($quoteRequest->responses as $quote)
                                @include('filament.customer.received-quotes.quote-card', ['quote' => $quote, 'quoteRequest' => $quoteRequest])
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</x-filament-panels::page>