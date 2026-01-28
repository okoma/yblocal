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

        <x-filament::button
            type="button"
            wire:click="resendEmailVerification"
            class="w-full"
        >
            Resend verification email
        </x-filament::button>
    </div>
</x-filament-panels::page.simple>