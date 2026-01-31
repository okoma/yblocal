@extends('layouts.app')

@php
    $locationName = ucfirst(request('city') ?: request('state'));
    $isCity = request('city') !== null;
@endphp

@section('title', 'Businesses in ' . $locationName . ' - ' . config('app.name'))

@section('meta')
    <meta name="description" content="Discover local businesses in {{ $locationName }}, Nigeria. Find verified hotels, restaurants, hospitals, schools, and more.">
    <meta name="keywords" content="businesses in {{ $locationName }}, {{ $locationName }} businesses, {{ $locationName }} directory">
@endsection

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Location Header -->
    <div class="mb-8">
        <div class="flex items-center gap-4 mb-4">
            <svg class="w-12 h-12 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    Businesses in {{ $locationName }}
                </h1>
                @if(request('category'))
                    <p class="text-lg text-gray-600 dark:text-gray-400 mt-1">
                        {{ ucfirst(request('category')) }} Category
                    </p>
                @endif
            </div>
        </div>
        
        <p class="text-gray-600 dark:text-gray-400">
            {{ number_format($businesses->total()) }} businesses found in {{ $locationName }}
        </p>
    </div>

    <!-- Breadcrumb -->
    <nav class="flex mb-6 text-sm text-gray-600 dark:text-gray-400">
        <a href="/" class="hover:text-gray-900 dark:hover:text-white">Home</a>
        <span class="mx-2">/</span>
        @if($isCity && request('state'))
            <a href="/{{ request('state') }}" class="hover:text-gray-900 dark:hover:text-white">{{ ucfirst(request('state')) }}</a>
            <span class="mx-2">/</span>
        @endif
        <span class="text-gray-900 dark:text-white">{{ $locationName }}</span>
        @if(request('category'))
            <span class="mx-2">/</span>
            <span class="text-gray-900 dark:text-white">{{ ucfirst(request('category')) }}</span>
        @endif
    </nav>

    <!-- Popular Categories in this Location -->
    @if(!request('category') && isset($categories) && $categories->isNotEmpty())
        <div class="mb-8 bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Popular Categories in {{ $locationName }}</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                @foreach($categories->take(12) as $category)
                    <a href="/{{ request('state') ?: request('city') }}/{{ $category->slug }}" 
                       class="flex flex-col items-center justify-center p-4 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg transition-colors">
                        @if($category->icon)
                            <span class="text-3xl mb-2">{{ $category->icon }}</span>
                        @endif
                        <span class="text-sm text-center text-gray-900 dark:text-white font-medium">{{ $category->name }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Active Filters Display -->
    @if(request()->except(['page', 'per_page', 'state', 'city'])->count() > 0)
        <div class="mb-6 flex flex-wrap items-center gap-2">
            <span class="text-sm text-gray-600 dark:text-gray-400">Active filters:</span>
            
            @if(request('category'))
                <span class="inline-flex items-center gap-1 px-3 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-full text-sm">
                    Category: {{ request('category') }}
                    <a href="{{ url()->current() }}?{{ http_build_query(request()->except(['category', 'page'])) }}" class="ml-1 hover:text-green-900">×</a>
                </span>
            @endif
            
            @if(request('business_type'))
                <span class="inline-flex items-center gap-1 px-3 py-1 bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 rounded-full text-sm">
                    Type: {{ request('business_type') }}
                    <a href="{{ url()->current() }}?{{ http_build_query(request()->except(['business_type', 'page'])) }}" class="ml-1 hover:text-purple-900">×</a>
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
                    </select>
                    
                    <!-- Filters Toggle Button -->
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <h3 class="mt-4 text-xl font-medium text-gray-900 dark:text-white">No businesses found in {{ $locationName }}</h3>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        @if(request()->except(['page', 'per_page', 'state', 'city'])->count() > 0)
                            Try adjusting your filters
                        @else
                            Check back later for new listings
                        @endif
                    </p>
                    @if(request()->except(['page', 'per_page', 'state', 'city'])->count() > 0)
                        <a href="{{ url()->current() }}" class="mt-4 inline-block px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Clear Filters
                        </a>
                    @endif
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
                :businessTypes="$businessTypes ?? collect()" 
                :categories="$categories ?? collect()" 
                :states="$states ?? collect()"
                :cities="$cities ?? collect()"
            />
        </div>
    </div>
</div>

@push('scripts')
<script>
    function toggleFilters() {
        const canvas = document.getElementById('filters-canvas');
        canvas.classList.toggle('hidden');
        
        // Prevent body scroll when drawer is open
        if (!canvas.classList.contains('hidden')) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }
    
    // Close on backdrop click
    document.addEventListener('DOMContentLoaded', function() {
        const canvas = document.getElementById('filters-canvas');
        canvas.addEventListener('click', function(e) {
            if (e.target === canvas) {
                toggleFilters();
            }
        });
        
        // Close on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !canvas.classList.contains('hidden')) {
                toggleFilters();
            }
        });
    });
</script>
@endpush

@endsection