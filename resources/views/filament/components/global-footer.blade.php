{{-- Global footer for all Filament panels (Admin, Business, Customer). --}}
<footer class="fi-footer mt-auto border-t border-gray-200 bg-white px-4 py-3 dark:border-white/5 dark:bg-gray-900">
    <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-2 text-sm text-gray-500 dark:text-gray-400">
        <span>© {{ date('Y') }} YellowBooks. All rights reserved.</span>
        <a href="{{ url('/') }}" class="hover:text-gray-700 dark:hover:text-gray-300">Home</a>
    </div>
</footer>
