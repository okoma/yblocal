<x-filament-panels::page.simple>
    <x-slot name="heading">
        Sign in to Business Portal
    </x-slot>

    <x-filament-panels::form wire:submit="authenticate">
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="$this->getCachedFormActions()"
            :full-width="$this->hasFullWidthFormActions()"
        />
    </x-filament-panels::form>

    <div class="mt-6 text-center text-sm text-gray-500">
        Don't have an account?
        <a class="text-primary-600 hover:underline" href="{{ url('/dashboard/register') }}">Create one</a>
    </div>
</x-filament-panels::page.simple>