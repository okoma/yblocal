<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Welcome Message --}}
        <div class="bg-gradient-to-r from-amber-500 to-amber-600 rounded-lg shadow-lg p-6 text-white">
            <h2 class="text-2xl font-bold mb-2">Welcome back, {{ auth()->user()->name }}! 👋</h2>
            <p class="text-amber-100">Manage your saved businesses, reviews, and inquiries all in one place.</p>
        </div>
        
        {{-- Stats Widgets --}}
        <x-filament-widgets::widgets
            :widgets="$this->getWidgets()"
            :columns="$this->getColumns()"
        />
        
        {{-- Quick Actions --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="/customer/saved-businesses" class="block p-6 bg-white rounded-lg shadow hover:shadow-md transition border-l-4 border-red-500">
                <div class="flex items-center">
                    <div class="p-3 bg-red-100 rounded-full">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900">Saved Businesses</h3>
                        <p class="text-sm text-gray-600">View your favorites</p>
                    </div>
                </div>
            </a>
            
            <a href="/customer/my-reviews" class="block p-6 bg-white rounded-lg shadow hover:shadow-md transition border-l-4 border-yellow-500">
                <div class="flex items-center">
                    <div class="p-3 bg-yellow-100 rounded-full">
                        <svg class="w-6 h-6 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900">My Reviews</h3>
                        <p class="text-sm text-gray-600">Manage your reviews</p>
                    </div>
                </div>
            </a>
            
            <a href="/customer/my-inquiries" class="block p-6 bg-white rounded-lg shadow hover:shadow-md transition border-l-4 border-blue-500">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 rounded-full">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900">My Inquiries</h3>
                        <p class="text-sm text-gray-600">Track your contacts</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
</x-filament-panels::page>
