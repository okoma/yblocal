<x-filament-panels::page.simple>
    <x-slot name="heading">
        Sign in to Business Portal
    </x-slot>

    <form method="POST" action="">
        @csrf
        {{ $this->form }}

        <x-filament::button
            type="submit"
            class="w-full mt-4"
        >
            Log in
        </x-filament::button>
    </form>

    <div class="mt-6 text-center text-sm text-gray-500">
        Don’t have an account?
        <a class="text-primary-600 hover:underline" href="{{ url('/dashboard/register') }}">Create one</a>
    </div>
</x-filament-panels::page.simple>

