<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}
        
        <x-filament::section>
            <x-slot name="heading">
                Actions
            </x-slot>
            
            <x-filament::button type="submit" size="lg">
                Save Payment Settings
            </x-filament::button>
        </x-filament::section>
    </form>
</x-filament-panels::page>
