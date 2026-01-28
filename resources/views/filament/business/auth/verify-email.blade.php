<x-filament-panels::page.simple>
    <x-slot name="heading">
        Verify your email
    </x-slot>

    <div class="space-y-4">
        <p class="text-sm text-gray-600">
            Please verify your email address to continue to the Business Portal.
        </p>

        {{ $this->form }}
    </div>
</x-filament-panels::page.simple>

