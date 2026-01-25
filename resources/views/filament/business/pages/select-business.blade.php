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
            <x-filament::section>
                <p class="text-gray-500 dark:text-gray-400 mb-4">You don't have any businesses yet.</p>
                <x-filament::button
                    tag="a"
                    href="{{ \App\Filament\Business\Resources\BusinessResource::getUrl('create') }}"
                    color="primary"
                    size="lg"
                    icon="heroicon-o-plus"
                >
                    Add New Business
                </x-filament::button>
            </x-filament::section>
        @endforelse
    </div>
</x-filament-panels::page>
