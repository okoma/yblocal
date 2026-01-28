<x-filament-panels::page.simple>
    <x-slot name="heading">
        Create Customer Account
    </x-slot>

    {{ $this->form }}

    <div class="mt-6 flex items-center gap-3">
        <div class="h-px flex-1 bg-gray-200"></div>
        <span class="text-xs uppercase tracking-wide text-gray-400">or</span>
        <div class="h-px flex-1 bg-gray-200"></div>
    </div>

    <div class="mt-4 grid grid-cols-2 gap-3">
        <x-filament::button
            tag="a"
            color="gray"
            class="w-full"
            href="{{ url('/customer/auth/google/redirect') }}"
        >
            Google
        </x-filament::button>

        <x-filament::button
            tag="a"
            color="gray"
            class="w-full"
            href="{{ url('/customer/auth/apple/redirect') }}"
        >
            Apple
        </x-filament::button>
    </div>

    <div class="mt-6 text-center text-sm text-gray-500">
        Already have an account?
        <a class="text-primary-600 hover:underline" href="{{ url('/customer/login') }}">Sign in</a>
    </div>
</x-filament-panels::page.simple>

