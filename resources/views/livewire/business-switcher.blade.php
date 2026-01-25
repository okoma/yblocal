<div class="fi-sidebar-item overflow-hidden" x-data="{ open: false }">
    <div
        class="fi-sidebar-item-button group flex w-full items-center gap-x-3 rounded-lg px-2 py-2 text-sm outline-none transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5 fi-sidebar-item-button"
        @click="open = !open"
    >
        <x-filament::icon icon="heroicon-o-building-storefront" class="fi-sidebar-item-icon h-5 w-5 shrink-0 text-gray-500 dark:text-gray-400" />
        <span class="fi-sidebar-item-label flex-1 truncate text-start text-gray-700 dark:text-gray-200">
            {{ $this->activeBusiness?->name ?? 'Select business' }}
        </span>
        <span class="fi-sidebar-item-icon h-4 w-4 shrink-0 text-gray-400 dark:text-gray-500 transition" :class="{ 'rotate-180': open }">
            <x-filament::icon icon="heroicon-m-chevron-down" class="h-4 w-4" />
        </span>
    </div>
    <div x-show="open" x-transition class="fi-sidebar-group-items mt-1 space-y-1 ps-2">
        @foreach ($this->businesses as $business)
            @if (($this->activeBusiness?->id ?? null) !== $business->id)
                <button
                    type="button"
                    wire:click="switchTo({{ $business->id }})"
                    class="fi-sidebar-item-button group flex w-full items-center gap-x-3 rounded-lg px-2 py-2 text-sm outline-none transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5"
                >
                    <span class="fi-sidebar-item-label flex-1 truncate text-start text-gray-600 dark:text-gray-300">
                        {{ $business->name }}
                    </span>
                </button>
            @endif
        @endforeach
    </div>
</div>
