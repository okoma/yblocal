
//resources/views/categories/show.blade.php
@extends('layouts.app')

@php
    $categoryModel = $categories->firstWhere('slug', request('category'));
    $categoryName = $categoryModel->name ?? request('category');
    $categoryIcon = $categoryModel->icon ?? null;
    $categoryDescription = $categoryModel->description ?? null;
@endphp

@section('title', $categoryName . ' - ' . config('app.name'))

@section('meta')
    <meta name="description" content="Browse {{ $categoryName }} businesses across Nigeria. Find verified and trusted {{ strtolower($categoryName) }} near you.">
    <meta name="keywords" content="{{ strtolower($categoryName) }}, {{ strtolower($categoryName) }} Nigeria, local {{ strtolower($categoryName) }}">
@endsection

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Category Header -->
    <div class="mb-8">
        <div class="flex items-center gap-4 mb-4">
            @if($categoryIcon)
                <span class="text-5xl">{{ $categoryIcon }}</span>
            @endif
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    {{ $categoryName }}
                </h1>
                @if(request('state'))
                    <p class="text-lg text-gray-600 dark:text-gray-400 mt-1">
                        in {{ ucfirst(request('state')) }}
                    </p>
                @elseif(request('city'))
                    <p class="text-lg text-gray-600 dark:text-gray-400 mt-1">
                        in {{ ucfirst(request('city')) }}
                    </p>
                @endif
            </div>
        </div>
        
        @if($categoryDescription)
            <p class="text-gray-600 dark:text-gray-400 max-w-3xl">
                {{ $categoryDescription }}
            </p>
        @endif
        
        <p class="text-gray-600 dark:text-gray-400 mt-2">
            {{ number_format($businesses->total()) }} businesses found
        </p>
    </div>

    <!-- Breadcrumb -->
    <nav class="flex mb-6 text-sm text-gray-600 dark:text-gray-400">
        <a href="/" class="hover:text-gray-900 dark:hover:text-white">Home</a>
        <span class="mx-2">/</span>
        @if(request('state'))
            <a href="/{{ request('state') }}" class="hover:text-gray-900 dark:hover:text-white">{{ ucfirst(request('state')) }}</a>
            <span class="mx-2">/</span>
        @endif
        <span class="text-gray-900 dark:text-white">{{ $categoryName }}</span>
    </nav>

    <!-- Active Filters Display -->
    @if(request()->except(['page', 'per_page', 'category'])->count() > 0)
        <div class="mb-6 flex flex-wrap items-center gap-2">
            <span class="text-sm text-gray-600 dark:text-gray-400">Active filters:</span>
            
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
            
            <a href="/{{ request('category') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
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
                    <h3 class="mt-4 text-xl font-medium text-gray-900 dark:text-white">No {{ strtolower($categoryName) }} found</h3>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        Try adjusting your filters or check back later
                    </p>
                    <a href="/{{ request('category') }}" class="mt-4 inline-block px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Clear Filters
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

@endsection
