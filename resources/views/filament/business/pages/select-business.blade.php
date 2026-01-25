<x-filament-panels::page>
    <div class="space-y-4">
        @forelse ($this->businesses as $business)
            <x-filament::button
                wire:click="selectBusiness({{ $business->id }})"
                color="primary"
                size="lg"
                class="w-full justify-start"
                icon="heroicon-o-building-storefront"
            >
                {{ $business->name }}
            </x-filament::button>
        @empty
            <div class="flex flex-col items-center justify-center py-12 px-4 text-center">
                <div class="mb-6">
                    <x-filament::icon
                        icon="heroicon-o-building-storefront"
                        class="h-24 w-24 text-gray-400 dark:text-gray-600 mx-auto"
                    />
                </div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    Get Started
                </h2>
                <p class="text-gray-600 dark:text-gray-400 mb-8 max-w-md">
                    Create your first business to start managing your listings, leads, reviews, and analytics all in one place.
                </p>
                <x-filament::button
                    tag="a"
                    href="{{ \App\Filament\Business\Resources\BusinessResource::getUrl('create') }}"
                    color="primary"
                    size="xl"
                    icon="heroicon-o-plus"
                >
                    Create Your First Business
                </x-filament::button>
            </div>
        @endforelse
    </div>
</x-filament-panels::page>
