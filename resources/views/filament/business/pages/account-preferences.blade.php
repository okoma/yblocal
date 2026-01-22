<x-filament-panels::page>
    <x-filament::card>
        <form wire:submit="save">
            {{ $this->form }}

            <div class="mt-6 flex items-center justify-between">
                <x-filament::button 
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="save"
                >
                    <span wire:loading.remove wire:target="save">Save Preferences</span>
                    <span wire:loading wire:target="save">Save Preferences</span>
                </x-filament::button>

                <x-filament::button 
                    color="gray" 
                    outlined
                    wire:click="fillForm"
                    wire:loading.attr="disabled"
                    wire:target="fillForm"
                >
                    <span wire:loading.remove wire:target="fillForm">Reset to Defaults</span>
                    <span wire:loading wire:target="fillForm">Reset to Defaults</span>
                </x-filament::button>
            </div>
        </form>
    </x-filament::card>
</x-filament-panels::page>