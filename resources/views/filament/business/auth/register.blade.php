<x-filament-panels::page.simple>
    <x-slot name="heading">
        Create Business Account
    </x-slot>

    <x-filament-panels::form wire:submit="register">
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="$this->getCachedFormActions()"
            :full-width="$this->hasFullWidthFormActions()"
        />
    </x-filament-panels::form>

    <div class="mt-6 text-center text-sm text-gray-500">
        Already have an account?
        <a class="text-primary-600 hover:underline" href="{{ url('/dashboard/login') }}">Sign in</a>
    </div>
</x-filament-panels::page.simple>