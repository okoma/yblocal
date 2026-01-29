<div class="p-4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg">
    <div class="flex items-start justify-between gap-4">
        <div class="flex-1 min-w-0">
            <div class="flex items-start gap-3">
                <div class="flex-1">
                    <h5 class="text-base font-semibold text-gray-900 dark:text-white">
                        {{ $quote->business->business_name ?? 'N/A' }}
                    </h5>
                    <div class="mt-1 flex items-center gap-2">
                        <span class="text-xl font-bold text-green-600 dark:text-green-400">
                            ₦{{ number_format($quote->price, 2) }}
                        </span>
                        @if($quote->delivery_time)
                            <span class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                <x-heroicon-o-clock class="h-4 w-4"/>
                                {{ $quote->delivery_time }}
                            </span>
                        @endif
                    </div>
                </div>
                <x-filament::badge :color="match($quote->status) {
                    'submitted' => 'gray',
                    'shortlisted' => 'info',
                    'accepted' => 'success',
                    'rejected' => 'danger',
                    default => 'gray',
                }">
                    {{ ucfirst($quote->status) }}
                </x-filament::badge>
            </div>

            @if($quote->message)
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    {{ $quote->message }}
                </p>
            @endif

            <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                Received {{ $quote->created_at->diffForHumans() }}
            </div>
        </div>
    </div>

    {{-- Actions --}}
    @if($quoteRequest->status === 'open')
        <div class="mt-4 flex flex-wrap gap-2">
            @if($quote->status === 'submitted')
                <x-filament::button
                    wire:click="shortlistQuote({{ $quote->id }})"
                    size="sm"
                    color="info"
                    icon="heroicon-o-star"
                >
                    Shortlist
                </x-filament::button>
            @endif

            @if(in_array($quote->status, ['submitted', 'shortlisted']))
                <x-filament::button
                    wire:click="acceptQuote({{ $quote->id }})"
                    wire:confirm="Are you sure you want to accept this quote? This will close the request and reject all other quotes."
                    size="sm"
                    color="success"
                    icon="heroicon-o-check-circle"
                >
                    Accept Quote
                </x-filament::button>

                <x-filament::button
                    wire:click="rejectQuote({{ $quote->id }})"
                    wire:confirm="Are you sure you want to reject this quote?"
                    size="sm"
                    color="danger"
                    icon="heroicon-o-x-circle"
                    outlined
                >
                    Reject
                </x-filament::button>
            @endif

            @if($quote->status === 'shortlisted')
                <x-filament::button
                    wire:click="removeFromShortlist({{ $quote->id }})"
                    wire:confirm="This quote will be moved back to submitted status."
                    size="sm"
                    color="gray"
                    icon="heroicon-o-x-mark"
                    outlined
                >
                    Remove from Shortlist
                </x-filament::button>
            @endif
        </div>
    @endif
</div>