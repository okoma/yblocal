<x-filament-panels::page>
    <x-filament::card>
        <form wire:submit="save">
            {{ $this->form }}

            <div class="mt-6 flex items-center justify-end">
                <x-filament::button type="submit">
                    Save Payment Settings
                </x-filament::button>
            </div>
        </form>
    </x-filament::card>
</x-filament-panels::page>
