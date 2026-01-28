<x-filament-panels::page.simple>
    <x-slot name="heading">
        Reset your password
    </x-slot>

    <x-filament-panels::form wire:submit="request">
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="$this->getCachedFormActions()"
            :full-width="$this->hasFullWidthFormActions()"
        />
    </x-filament-panels::form>

    <div class="mt-6 text-center text-sm text-gray-500">
        Remember your password?
        <a class="text-primary-600 hover:underline" href="{{ url('/customer/login') }}">Back to login</a>
    </div>
</x-filament-panels::page.simple>