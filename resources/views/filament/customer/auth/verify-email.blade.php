<x-filament-panels::page.simple>
    <x-slot name="heading">
        Verify your email
    </x-slot>

    <div class="space-y-4">
        <p class="text-sm text-gray-600">
            We've sent a verification link to your email address. Please check your inbox and click the link to verify your account.
        </p>

        <p class="text-sm text-gray-600">
            Didn't receive the email?
        </p>

        <x-filament-panels::form wire:submit="sendNotification">
            {{ $this->form }}

            <x-filament-panels::form.actions
                :actions="$this->getCachedFormActions()"
                :full-width="$this->hasFullWidthFormActions()"
            />
        </x-filament-panels::form>
    </div>
</x-filament-panels::page.simple>