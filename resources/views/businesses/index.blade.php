
//resources/views/businesses/index.blade.php
@extends('layouts.app')

@section('title', 'Discover Local Businesses - ' . config('app.name'))

@section('meta')
    <meta name="description" content="Discover and connect with verified local businesses across Nigeria. Find hotels, restaurants, hospitals, schools, and more.">
    <meta name="keywords" content="business listing, local businesses, Nigeria, directory">
@endsection

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
            Discover Local Businesses
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
            Browse {{ number_format($businesses->total()) }} verified businesses across Nigeria
        </p>
    </div>

    <!-- Active Filters Display -->
    @if(request()->except(['page', 'per_page'])->count() > 0)
        <div class="mb-6 flex flex-wrap items-center gap-2">
            <span class="text-sm text-gray-600 dark:text-gray-400">Active filters:</span>
            
            @if(request('q'))
                <span class="inline-flex items-center gap-1 px-3 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-full text-sm">
                    Search: "{{ request('q') }}"
                    <a href="{{ url()->current() }}?{{ http_build_query(request()->except(['q', 'page'])) }}" class="ml-1 hover:text-blue-900">×</a>
                </span>
            @endif
            
            @if(request('business_type'))
                <span class="inline-flex items-center gap-1 px-3 py-1 bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 rounded-full text-sm">
                    Type: {{ request('business_type') }}
                    <a href="{{ url()->current() }}?{{ http_build_query(request()->except(['business_type', 'page'])) }}" class="ml-1 hover:text-purple-900">×</a>
                </span>
            @endif
            
            @if(request('category'))
                <span class="inline-flex items-center gap-1 px-3 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-full text-sm">
                    Category: {{ request('category') }}
                    <a href="{{ url()->current() }}?{{ http_build_query(request()->except(['category', 'page'])) }}" class="ml-1 hover:text-green-900">×</a>
                </span>
            @endif
            
            @if(request('state'))
                <span class="inline-flex items-center gap-1 px-3 py-1 bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200 rounded-full text-sm">
                    State: {{ request('state') }}
                    <a href="{{ url()->current() }}?{{ http_build_query(request()->except(['state', 'city', 'page'])) }}" class="ml-1 hover:text-orange-900">×</a>
                </span>
            @endif
            
            @if(request('city'))
                <span class="inline-flex items-center gap-1 px-3 py-1 bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200 rounded-full text-sm">
                    City: {{ request('city') }}
                    <a href="{{ url()->current() }}?{{ http_build_query(request()->except(['city', 'page'])) }}" class="ml-1 hover:text-orange-900">×</a>
                </span>
            @endif
            
            <a href="{{ url()->current() }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                Clear all
            </a>
        </div>
    @endif

    <!-- Main Content (Full Width) -->
    <div>
        <div class="flex-1">
            <!-- Sort & View Options -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <!-- Results Count -->
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    Showing {{ $businesses->firstItem() ?? 0 }}-{{ $businesses->lastItem() ?? 0 }} of {{ number_format($businesses->total()) }} results
                </div>

                <!-- Sort Options -->
                <div class="flex items-center gap-4">
                    <label class="text-sm text-gray-600 dark:text-gray-400">Sort by:</label>
                    <select 
                        name="sort" 
                        onchange="window.location.href='{{ url()->current() }}?{{ http_build_query(request()->except(['sort', 'page'])) }}&sort=' + this.value"
                        class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm dark:bg-gray-700 dark:text-white"
                    >
                        <option value="relevance" {{ request('sort') === 'relevance' ? 'selected' : '' }}>Relevance</option>
                        <option value="rating" {{ request('sort') === 'rating' ? 'selected' : '' }}>Highest Rated</option>
                        <option value="reviews" {{ request('sort') === 'reviews' ? 'selected' : '' }}>Most Reviewed</option>
                        <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>Newest</option>
                        <option value="name" {{ request('sort') === 'name' ? 'selected' : '' }}>Alphabetical</option>
                        <option value="distance" {{ request('sort') === 'distance' ? 'selected' : '' }}>Distance</option>
                    </select>
                    
                    <!-- Filters Toggle (All Devices) -->
                    <button 
                        type="button" 
                        onclick="toggleFilters()"
                        class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                        </svg>
                        Filters
                    </button>
                </div>
            </div>

            <!-- Business Grid -->
            @if($businesses->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mb-8">
                    @foreach($businesses as $business)
                        <x-business-card :business="$business" />
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-8">
                    {{ $businesses->links() }}
                </div>
            @else
                <!-- Empty State -->
                <div class="text-center py-16">
                    <svg class="mx-auto h-24 w-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h3 class="mt-4 text-xl font-medium text-gray-900 dark:text-white">No businesses found</h3>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        Try adjusting your filters or search criteria
                    </p>
                    <a href="{{ url()->current() }}" class="mt-4 inline-block px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Clear Filters
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Filters Canvas (Offcanvas Drawer) -->
<div id="filters-canvas" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50">
    <div class="absolute right-0 top-0 bottom-0 w-full sm:w-96 bg-white dark:bg-gray-800 shadow-2xl transform transition-transform duration-300 overflow-y-auto">
        <div class="sticky top-0 bg-white dark:bg-gray-800 p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center z-10">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                </svg>
                Filters
            </h2>
            <button onclick="toggleFilters()" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="p-6">
            <x-filters-sidebar 
                :businessTypes="$businessTypes" 
                :categories="$categories" 
                :states="$states"
                :cities="$cities ?? []"
                :activeFilters="$activeFilters ?? []"
            />
        </div>
    </div>
</div>

@push('scripts')
<script>
    function toggleFilters() {
        const canvas = document.getElementById('filters-canvas');
        canvas.classList.toggle('hidden');
    }
    
    // Close on backdrop click
    document.addEventListener('DOMContentLoaded', function() {
        const canvas = document.getElementById('filters-canvas');
        canvas.addEventListener('click', function(e) {
            if (e.target === canvas) {
                toggleFilters();
            }
        });
    });
</script>
@endpush
@endsection
