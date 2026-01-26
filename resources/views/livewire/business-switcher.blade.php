
<div
    class="business-switcher-wrapper fi-sidebar-item relative z-20 overflow-visible border-b border-gray-200 dark:border-white/10 pt-2 pb-2 mb-2"
    x-data="{ open: false }"
    @click.outside="open = false"
>
    <div
        class="fi-sidebar-item-button group flex w-full items-center gap-x-3 rounded-lg px-3 py-2.5 text-sm outline-none transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5 cursor-pointer"
        @click="open = !open"
    >
        <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-800">
            <x-filament::icon icon="heroicon-o-building-storefront" class="h-4 w-4 text-gray-600 dark:text-gray-400" />
        </div>
        <div class="flex-1 min-w-0">
            <span class="block text-xs text-gray-500 dark:text-gray-400 font-medium">Current Business</span>
            <span class="block truncate text-sm font-semibold text-gray-700 dark:text-gray-200">
                {{ $this->activeBusiness?->name ?? 'Select business' }}
            </span>
        </div>
        <span class="fi-sidebar-item-icon h-5 w-5 shrink-0 text-gray-400 dark:text-gray-500 transition-transform duration-200" :class="{ 'rotate-180': open }">
            <x-filament::icon icon="heroicon-m-chevron-down" class="h-5 w-5" />
        </span>
    </div>
    
    <div 
        x-show="open" 
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1"
        class="fi-sidebar-group-items mt-1 space-y-1 ps-2 overflow-visible dropdown-content"
    >
        <div class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider px-2 py-1.5">
            Your Businesses
        </div>
        
        @foreach ($this->businesses as $business)
            @php
                $isActive = ($this->activeBusiness?->id ?? null) === $business->id;
            @endphp
            <button
                type="button"
                wire:click="switchTo({{ $business->id }})"
                @if ($isActive) @click="open = false" @endif
                class="business-item {{ $isActive ? 'active bg-gray-50 dark:bg-gray-800' : '' }} group flex w-full items-center gap-x-3 rounded-lg px-3 py-2.5 text-sm outline-none transition-all duration-150 hover:bg-gray-50 dark:hover:bg-white/5"
            >
                <div class="flex-1 min-w-0">
                    <span class="block truncate text-start {{ $isActive ? 'text-gray-900 dark:text-gray-100 font-semibold' : 'text-gray-600 dark:text-gray-300 font-medium' }}">
                        {{ $business->name }}
                    </span>
                    @if ($isActive)
                        <span class="block text-xs text-gray-500 dark:text-gray-400 mt-0.5">Currently active</span>
                    @endif
                </div>
                @if ($isActive)
                    <div class="flex items-center gap-1.5">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                            Active
                        </span>
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 shrink-0 text-green-600 dark:text-green-400" />
                    </div>
                @else
                    <x-filament::icon icon="heroicon-m-arrow-right" class="h-4 w-4 shrink-0 text-gray-400 dark:text-gray-500 opacity-0 group-hover:opacity-100 transition-opacity" />
                @endif
            </button>
        @endforeach
        
        @if ($this->businesses->isNotEmpty())
            <div class="business-divider">
                <a
                    href="{{ \App\Filament\Business\Resources\BusinessResource::getUrl('create') }}"
                    class="create-business-btn group flex w-full items-center gap-x-3 rounded-lg px-3 py-2.5 text-sm outline-none transition-all duration-150 hover:bg-gray-100 dark:hover:bg-gray-700"
                    @click="open = false"
                >
                    <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-white dark:bg-gray-800 shadow-sm">
                        <x-filament::icon icon="heroicon-o-plus" class="h-4 w-4 text-primary-600 dark:text-primary-400" />
                    </div>
                    <span class="flex-1 truncate text-start font-semibold text-primary-600 dark:text-primary-400">
                        Create New Business
                    </span>
                    <x-filament::icon icon="heroicon-m-arrow-right" class="h-4 w-4 shrink-0 text-primary-600 dark:text-primary-400 opacity-0 group-hover:opacity-100 transition-opacity" />
                </a>
            </div>
        @endif
    </div>
</div>