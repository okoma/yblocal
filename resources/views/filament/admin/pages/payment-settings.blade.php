<x-filament-panels::page>
    <x-filament::card>
        <form wire:submit="save">
            {{ $this->form }}

            <div class="mt-6 flex items-center justify-end">
                <x-filament::button 
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="save"
                >
                    <span wire:loading.remove wire:target="save">Save Payment Settings</span>
                    <span wire:loading wire:target="save">Save Payment Settings</span>
                </x-filament::button>
            </div>
        </form>
    </x-filament::card>
</x-filament-panels::page>
