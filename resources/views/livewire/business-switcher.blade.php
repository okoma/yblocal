<div
    class="business-switcher-wrapper fi-sidebar-item relative z-20 overflow-visible border-b border-gray-200 dark:border-white/10 pt-2 pb-2 mb-2"
    x-data="{ open: false }"
    @click.outside="open = false"
>
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
    <div x-show="open" x-transition class="fi-sidebar-group-items mt-1 space-y-1 ps-2 overflow-visible">
        @foreach ($this->businesses as $business)
            @php
                $isActive = ($this->activeBusiness?->id ?? null) === $business->id;
            @endphp
            <button
                type="button"
                wire:click="switchTo({{ $business->id }})"
                @if ($isActive) @click="open = false" @endif
                class="fi-sidebar-item-button group flex w-full items-center gap-x-3 rounded-lg px-2 py-2 text-sm outline-none transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5 {{ $isActive ? 'bg-primary-50 dark:bg-primary-900/20' : '' }}"
            >
                <span class="fi-sidebar-item-label flex-1 truncate text-start {{ $isActive ? 'text-primary-600 dark:text-primary-400 font-medium' : 'text-gray-600 dark:text-gray-300' }}">
                    {{ $business->name }}
                </span>
                @if ($isActive)
                    <x-filament::icon icon="heroicon-m-check-circle" class="h-4 w-4 shrink-0 text-primary-600 dark:text-primary-400" />
                @endif
            </button>
        @endforeach
        @if ($this->businesses->isNotEmpty())
            <div class="border-t border-gray-200 dark:border-white/10 mt-1 pt-1">
                <a
                    href="{{ \App\Filament\Business\Resources\BusinessResource::getUrl('create') }}"
                    class="fi-sidebar-item-button group flex w-full items-center gap-x-3 rounded-lg px-2 py-2 text-sm outline-none transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5 text-primary-600 dark:text-primary-400"
                    @click="open = false"
                >
                    <x-filament::icon icon="heroicon-o-plus" class="fi-sidebar-item-icon h-5 w-5 shrink-0" />
                    <span class="fi-sidebar-item-label flex-1 truncate text-start font-medium">
                        Create New Business
                    </span>
                </a>
            </div>
        @endif
    </div>
</div>
