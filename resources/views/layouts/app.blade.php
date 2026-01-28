<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Business Listing'))</title>
    
    @yield('meta')

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Styles -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3/dist/tailwind.min.css" rel="stylesheet">
    @endif
    
    <!-- Livewire Styles -->
    @livewireStyles
    
    @stack('styles')
</head>
<body class="bg-gray-50 dark:bg-gray-900 antialiased">
    <!-- Header -->
    <header class="bg-white dark:bg-gray-800 shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="/" class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ config('app.name', 'YBLocal') }}
                    </a>
                </div>

                <!-- Search Bar (Desktop) -->
                <div class="hidden md:flex flex-1 max-w-2xl mx-8">
                    <form action="{{ route('businesses.search') }}" method="GET" class="w-full">
                        <div class="relative">
                            <input 
                                type="text" 
                                name="q" 
                                value="{{ request('q') }}" 
                                placeholder="Search for businesses, categories, locations..."
                                class="w-full px-4 py-2.5 pl-10 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                            <svg class="absolute left-3 top-3 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                    </form>
                </div>

                <!-- Navigation -->
                <nav class="flex items-center space-x-4">
                    @auth
                        <a href="{{ url('/business') }}" class="text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                            Log in
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="bg-blue-600 text-white hover:bg-blue-700 px-4 py-2 rounded-md text-sm font-medium">
                                List Your Business
                            </a>
                        @endif
                    @endauth
                </nav>
            </div>

            <!-- Mobile Search -->
            <div class="md:hidden pb-4">
                <form action="{{ route('businesses.search') }}" method="GET">
                    <div class="relative">
                        <input 
                            type="text" 
                            name="q" 
                            value="{{ request('q') }}" 
                            placeholder="Search..."
                            class="w-full px-4 py-2 pl-10 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        >
                        <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </form>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        {{ config('app.name', 'YBLocal') }}
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">
                        Discover and connect with local businesses across Nigeria.
                    </p>
                </div>
                
                <div>
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">For Businesses</h4>
                    <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        <li><a href="#" class="hover:text-gray-900 dark:hover:text-white">List Your Business</a></li>
                        <li><a href="#" class="hover:text-gray-900 dark:hover:text-white">Pricing</a></li>
                        <li><a href="#" class="hover:text-gray-900 dark:hover:text-white">Business Login</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Discover</h4>
                    <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        <li><a href="{{ route('discover.index') }}" class="hover:text-gray-900 dark:hover:text-white">Browse Businesses</a></li>
                        <li><a href="/hotels" class="hover:text-gray-900 dark:hover:text-white">Hotels</a></li>
                        <li><a href="/restaurants" class="hover:text-gray-900 dark:hover:text-white">Restaurants</a></li>
                        <li><a href="/hospitals" class="hover:text-gray-900 dark:hover:text-white">Hospitals</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Support</h4>
                    <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        <li><a href="#" class="hover:text-gray-900 dark:hover:text-white">Help Center</a></li>
                        <li><a href="#" class="hover:text-gray-900 dark:hover:text-white">Contact Us</a></li>
                        <li><a href="#" class="hover:text-gray-900 dark:hover:text-white">Terms of Service</a></li>
                        <li><a href="#" class="hover:text-gray-900 dark:hover:text-white">Privacy Policy</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-200 dark:border-gray-700 mt-8 pt-8 text-center text-sm text-gray-600 dark:text-gray-400">
                <p>&copy; {{ date('Y') }} {{ config('app.name', 'YBLocal') }}. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Livewire Scripts -->
    @livewireScripts
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    @stack('scripts')
</body>
</html>
