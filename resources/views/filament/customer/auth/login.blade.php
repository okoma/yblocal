@php
    $panelId = 'customer';
@endphp

<x-filament-panels::page.simple>
    <x-slot name="heading">
        Sign in to Customer Portal
    </x-slot>

    {{ $this->form }}

    <x-filament::button
        tag="a"
        color="gray"
        class="w-full mt-4"
        href="{{ url('/customer/auth/google/redirect') }}"
    >
        Continue with Google
    </x-filament::button>

    <x-filament::button
        tag="a"
        color="gray"
        class="w-full mt-2"
        href="{{ url('/customer/auth/apple/redirect') }}"
    >
        Continue with Apple
    </x-filament::button>

    <div class="mt-6 text-center text-sm text-gray-500">
        Don’t have an account?
        <a class="text-primary-600 hover:underline" href="{{ url('/customer/register') }}">Create one</a>
    </div>
</x-filament-panels::page.simple>

