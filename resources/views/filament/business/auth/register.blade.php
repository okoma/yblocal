<x-filament-panels::page.simple>
    <x-slot name="heading">
        Create Business Owner Account
    </x-slot>

    <form method="POST" action="">
        @csrf
        {{ $this->form }}

        <x-filament::button
            type="submit"
            class="w-full mt-4"
        >
            Create account
        </x-filament::button>
    </form>

    <div class="mt-6 text-center text-sm text-gray-500">
        Already have an account?
        <a class="text-primary-600 hover:underline" href="{{ url('/dashboard/login') }}">Sign in</a>
    </div>
</x-filament-panels::page.simple>

