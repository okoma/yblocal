<x-filament-panels::page.simple>
    <x-slot name="heading">
        Create Customer Account
    </x-slot>

    {{ $this->form }}

    <x-filament::button
        tag="a"
        color="gray"
        class="w-full mt-4"
        href="{{ url('/customer/auth/google/redirect') }}"
    >
        Sign up with Google
    </x-filament::button>

    <x-filament::button
        tag="a"
        color="gray"
        class="w-full mt-2"
        href="{{ url('/customer/auth/apple/redirect') }}"
    >
        Sign up with Apple
    </x-filament::button>

    <div class="mt-6 text-center text-sm text-gray-500">
        Already have an account?
        <a class="text-primary-600 hover:underline" href="{{ url('/customer/login') }}">Sign in</a>
    </div>
</x-filament-panels::page.simple>

