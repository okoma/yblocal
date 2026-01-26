@props(['business'])

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-md hover:shadow-xl transition-shadow duration-300 overflow-hidden">
    <!-- Business Image -->
    <a href="{{ $business->getUrl() }}" class="block relative h-48 overflow-hidden bg-gray-200 dark:bg-gray-700">
        @if($business->cover_photo)
            <img 
                src="{{ Storage::url($business->cover_photo) }}" 
                alt="{{ $business->business_name }}"
                class="w-full h-full object-cover"
            >
        @elseif($business->logo)
            <img 
                src="{{ Storage::url($business->logo) }}" 
                alt="{{ $business->business_name }}"
                class="w-full h-full object-contain p-8"
            >
        @else
            <div class="w-full h-full flex items-center justify-center text-gray-400">
                <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
            </div>
        @endif
        
        <!-- Premium Badge -->
        @if($business->is_premium)
            <div class="absolute top-2 right-2 bg-yellow-500 text-white px-2 py-1 rounded-md text-xs font-semibold">
                PREMIUM
            </div>
        @endif
        
        <!-- Verified Badge -->
        @if($business->is_verified)
            <div class="absolute top-2 left-2 bg-blue-600 text-white px-2 py-1 rounded-md text-xs font-semibold flex items-center gap-1">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                VERIFIED
            </div>
        @endif
    </a>

    <!-- Business Info -->
    <div class="p-4">
        <!-- Business Type -->
        @if($business->businessType)
            <div class="flex items-center gap-2 mb-2">
                @if($business->businessType->icon)
                    <span class="text-lg">{{ $business->businessType->icon }}</span>
                @endif
                <span class="text-xs font-medium text-gray-600 dark:text-gray-400">
                    {{ $business->businessType->name }}
                </span>
            </div>
        @endif

        <!-- Business Name -->
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
            <a href="{{ $business->getUrl() }}" class="hover:text-blue-600 dark:hover:text-blue-400">
                {{ $business->business_name }}
            </a>
        </h3>

        <!-- Rating & Reviews -->
        <div class="flex items-center gap-2 mb-3">
            @if($business->avg_rating > 0)
                <div class="flex items-center">
                    @for($i = 1; $i <= 5; $i++)
                        <svg class="w-4 h-4 {{ $i <= round($business->avg_rating) ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                    @endfor
                    <span class="ml-2 text-sm font-medium text-gray-900 dark:text-white">
                        {{ number_format($business->avg_rating, 1) }}
                    </span>
                </div>
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    ({{ number_format($business->total_reviews) }} reviews)
                </span>
            @else
                <span class="text-sm text-gray-500 dark:text-gray-400">No reviews yet</span>
            @endif
        </div>

        <!-- Categories -->
        @if($business->categories->isNotEmpty())
            <div class="flex flex-wrap gap-1 mb-3">
                @foreach($business->categories->take(3) as $category)
                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-{{ $category->color ?? 'gray' }}-100 text-{{ $category->color ?? 'gray' }}-800 dark:bg-{{ $category->color ?? 'gray' }}-900 dark:text-{{ $category->color ?? 'gray' }}-200">
                        @if($category->icon)
                            <span class="mr-1">{{ $category->icon }}</span>
                        @endif
                        {{ $category->name }}
                    </span>
                @endforeach
                @if($business->categories->count() > 3)
                    <span class="text-xs text-gray-500 dark:text-gray-400">+{{ $business->categories->count() - 3 }} more</span>
                @endif
            </div>
        @endif

        <!-- Location -->
        <div class="flex items-start gap-2 text-sm text-gray-600 dark:text-gray-400 mb-3">
            <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            <span>
                @if($business->area){{ $business->area }}, @endif
                @if($business->cityLocation)
                    {{ $business->cityLocation->name }}, 
                @elseif($business->city)
                    {{ $business->city }}, 
                @endif
                @if($business->stateLocation)
                    {{ $business->stateLocation->name }}
                @elseif($business->state)
                    {{ $business->state }}
                @endif
            </span>
        </div>

        <!-- Description -->
        @if($business->description)
            <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2 mb-4">
                {{ $business->description }}
            </p>
        @endif

        <!-- Actions -->
        <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-700">
            <a href="{{ $business->getUrl() }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 text-sm font-medium">
                View Details →
            </a>
            
            @if($business->phone)
                <a href="tel:{{ $business->phone }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                    </svg>
                </a>
            @endif
        </div>
    </div>
</div>
