@extends('layouts.app')

@section('title', $business->business_name . ' - ' . config('app.name'))

@section('meta')
    <meta name="description" content="{{ $business->description ?? 'Visit ' . $business->business_name . ' - A trusted ' . ($business->businessType->name ?? 'business') . ' in ' . ($business->city ?? $business->state) }}">
    <meta name="keywords" content="{{ $business->business_name }}, {{ $business->businessType->name ?? '' }}, {{ $business->city }}, {{ $business->state }}">
    
    <!-- Open Graph Meta -->
    <meta property="og:title" content="{{ $business->business_name }}">
    <meta property="og:description" content="{{ Str::limit($business->description, 160) }}">
    @if($business->cover_photo)
        <meta property="og:image" content="{{ Storage::url($business->cover_photo) }}">
    @endif
    <meta property="og:url" content="{{ $business->getCanonicalUrl() }}">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="{{ $business->getCanonicalUrl() }}">
@endsection

@section('content')
<div class="bg-gray-50 dark:bg-gray-900">
    <!-- Hero Section with Cover Photo -->
    <div class="relative h-80 bg-gray-300 dark:bg-gray-700">
        @if($business->cover_photo)
            <img 
                src="{{ Storage::url($business->cover_photo) }}" 
                alt="{{ $business->business_name }}"
                class="w-full h-full object-cover"
            >
        @else
            <div class="w-full h-full flex items-center justify-center text-gray-400">
                <svg class="w-32 h-32" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
            </div>
        @endif
        
        <!-- Overlay Gradient -->
        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Business Header Card -->
        <div class="relative -mt-32 mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6">
                <div class="flex flex-col md:flex-row gap-6">
                    <!-- Logo -->
                    <div class="flex-shrink-0">
                        @if($business->logo)
                            <img 
                                src="{{ Storage::url($business->logo) }}" 
                                alt="{{ $business->business_name }} Logo"
                                class="w-32 h-32 rounded-lg object-cover bg-white shadow-md"
                            >
                        @else
                            <div class="w-32 h-32 rounded-lg bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                        @endif
                    </div>

                    <!-- Business Info -->
                    <div class="flex-1">
                        <!-- Badges -->
                        <div class="flex flex-wrap gap-2 mb-3">
                            @if($business->is_verified)
                                <span class="inline-flex items-center px-3 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-full text-sm font-medium">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    Verified
                                </span>
                            @endif
                            
                            @if($business->is_premium)
                                <span class="inline-flex items-center px-3 py-1 bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 rounded-full text-sm font-medium">
                                    Premium
                                </span>
                            @endif
                            
                            @if($isOpen !== null)
                                <span class="inline-flex items-center px-3 py-1 {{ $isOpen ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' : 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200' }} rounded-full text-sm font-medium">
                                    {{ $isOpen ? 'Open Now' : 'Closed' }}
                                </span>
                            @endif
                        </div>

                        <!-- Business Name & Type -->
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                            {{ $business->business_name }}
                        </h1>
                        
                        @if($business->businessType)
                            <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400 mb-4">
                                @if($business->businessType->icon)
                                    <span class="text-xl">{{ $business->businessType->icon }}</span>
                                @endif
                                <span class="text-lg">{{ $business->businessType->name }}</span>
                            </div>
                        @endif

                        <!-- Rating & Reviews -->
                        <div class="flex items-center gap-4 mb-4">
                            @if($ratingSummary['avg_rating'] > 0)
                                <div class="flex items-center">
                                    @for($i = 1; $i <= 5; $i++)
                                        <svg class="w-5 h-5 {{ $i <= round($ratingSummary['avg_rating']) ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                        </svg>
                                    @endfor
                                    <span class="ml-2 text-xl font-bold text-gray-900 dark:text-white">
                                        {{ number_format($ratingSummary['avg_rating'], 1) }}
                                    </span>
                                </div>
                                <a href="#reviews" class="text-blue-600 dark:text-blue-400 hover:underline">
                                    {{ number_format($ratingSummary['total_reviews']) }} reviews
                                </a>
                            @else
                                <span class="text-gray-500 dark:text-gray-400">No reviews yet - Be the first to review!</span>
                            @endif
                        </div>

                        <!-- Location -->
                        <div class="flex items-start gap-2 text-gray-700 dark:text-gray-300">
                            <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span>
                                @if($business->address){{ $business->address }}, @endif
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
                    </div>

                    <!-- Quick Actions -->
                    <div class="flex flex-col gap-3 md:w-64">
                        @if($business->phone)
                            <a href="tel:{{ $business->phone }}" class="flex items-center justify-center gap-2 px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg>
                                Call Now
                            </a>
                        @endif
                        
                        @if($business->email)
                            <a href="mailto:{{ $business->email }}" class="flex items-center justify-center gap-2 px-4 py-3 bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 font-medium">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                Email
                            </a>
                        @endif
                        
                        @if($business->website)
                            <a href="{{ $business->website }}" target="_blank" rel="noopener" class="flex items-center justify-center gap-2 px-4 py-3 bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 font-medium">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                                </svg>
                                Website
                            </a>
                        @endif
                        
                        <button 
                            onclick="document.getElementById('inquiry-modal').classList.remove('hidden')"
                            class="flex items-center justify-center gap-2 px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                            </svg>
                            Send Inquiry
                        </button>
                    </div>
                </div>

                <!-- Categories -->
                @if($business->categories->isNotEmpty())
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach($business->categories as $category)
                            <a href="/{{ $category->slug }}" class="inline-flex items-center px-3 py-1 rounded-md text-sm font-medium bg-{{ $category->color ?? 'gray' }}-100 text-{{ $category->color ?? 'gray' }}-800 dark:bg-{{ $category->color ?? 'gray' }}-900 dark:text-{{ $category->color ?? 'gray' }}-200 hover:opacity-80">
                                @if($category->icon)
                                    <span class="mr-1">{{ $category->icon }}</span>
                                @endif
                                {{ $category->name }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="flex flex-col lg:flex-row gap-8 pb-12">
            <!-- Main Content -->
            <div class="flex-1 space-y-8">
                <!-- About Section -->
                @if($business->description)
                    <section class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">About</h2>
                        <p class="text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-line">
                            {{ $business->description }}
                        </p>
                    </section>
                @endif

                <!-- Amenities -->
                @if($business->amenities->isNotEmpty())
                    <section class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Amenities</h2>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            @foreach($business->amenities as $amenity)
                                <div class="flex items-center gap-2">
                                    @if($amenity->icon)
                                        <span class="text-xl">{{ $amenity->icon }}</span>
                                    @else
                                        <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                    @endif
                                    <span class="text-gray-700 dark:text-gray-300">{{ $amenity->name }}</span>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                <!-- Products/Services -->
                @if($business->products->isNotEmpty())
                    <section class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Products & Services</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($business->products as $product)
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                    <div class="flex justify-between items-start mb-2">
                                        <h3 class="font-semibold text-gray-900 dark:text-white">{{ $product->name }}</h3>
                                        @if($product->price)
                                            <span class="text-blue-600 dark:text-blue-400 font-semibold">
                                                ₦{{ number_format($product->price) }}
                                            </span>
                                        @endif
                                    </div>
                                    @if($product->description)
                                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $product->description }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                <!-- FAQs -->
                @if($business->faqs->isNotEmpty())
                    <section class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Frequently Asked Questions</h2>
                        <div class="space-y-4">
                            @foreach($business->faqs as $faq)
                                <details class="border-b border-gray-200 dark:border-gray-700 pb-4">
                                    <summary class="font-semibold text-gray-900 dark:text-white cursor-pointer hover:text-blue-600 dark:hover:text-blue-400">
                                        {{ $faq->question }}
                                    </summary>
                                    <p class="mt-2 text-gray-700 dark:text-gray-300">{{ $faq->answer }}</p>
                                </details>
                            @endforeach
                        </div>
                    </section>
                @endif

                <!-- Reviews Section -->
                <section id="reviews" class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Reviews</h2>
                        <button 
                            onclick="document.getElementById('review-modal').classList.remove('hidden')"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium"
                        >
                            Write a Review
                        </button>
                    </div>

                    <!-- Rating Summary -->
                    @if($ratingSummary['total_reviews'] > 0)
                        <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="flex items-center gap-8">
                                <div class="text-center">
                                    <div class="text-5xl font-bold text-gray-900 dark:text-white">
                                        {{ number_format($ratingSummary['avg_rating'], 1) }}
                                    </div>
                                    <div class="flex items-center justify-center mt-2">
                                        @for($i = 1; $i <= 5; $i++)
                                            <svg class="w-4 h-4 {{ $i <= round($ratingSummary['avg_rating']) ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                            </svg>
                                        @endfor
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        {{ number_format($ratingSummary['total_reviews']) }} reviews
                                    </div>
                                </div>
                                
                                <div class="flex-1 space-y-2">
                                    @foreach([5, 4, 3, 2, 1] as $rating)
                                        @php
                                            $count = $ratingSummary['rating_breakdown'][$rating] ?? 0;
                                            $percentage = $ratingSummary['total_reviews'] > 0 
                                                ? ($count / $ratingSummary['total_reviews']) * 100 
                                                : 0;
                                        @endphp
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm text-gray-600 dark:text-gray-400 w-12">{{ $rating }} stars</span>
                                            <div class="flex-1 bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                                <div class="bg-yellow-400 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                                            </div>
                                            <span class="text-sm text-gray-600 dark:text-gray-400 w-12 text-right">{{ $count }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Reviews List (loaded via iframe) -->
                    <div id="reviews-list" class="space-y-4">
                        <iframe 
                            src="{{ route('businesses.reviews.index', ['businessType' => $business->businessType->slug, 'slug' => $business->slug]) }}" 
                            class="w-full border-0"
                            style="min-height: 600px;"
                            onload="this.style.height = this.contentWindow.document.documentElement.scrollHeight + 'px';"
                        ></iframe>
                    </div>
                </section>
            </div>

            <!-- Sidebar -->
            <aside class="lg:w-96 space-y-6">
                <!-- Contact Info -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Contact Information</h3>
                    
                    <div class="space-y-3">
                        @if($business->phone)
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg>
                                <div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Phone</div>
                                    <a href="tel:{{ $business->phone }}" class="text-gray-900 dark:text-white hover:text-blue-600">{{ $business->phone }}</a>
                                </div>
                            </div>
                        @endif

                        @if($business->email)
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                <div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Email</div>
                                    <a href="mailto:{{ $business->email }}" class="text-gray-900 dark:text-white hover:text-blue-600 break-all">{{ $business->email }}</a>
                                </div>
                            </div>
                        @endif

                        @if($business->website)
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                                </svg>
                                <div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Website</div>
                                    <a href="{{ $business->website }}" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 hover:underline break-all">
                                        {{ parse_url($business->website, PHP_URL_HOST) }}
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Business Hours -->
                @if($business->business_hours)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Business Hours</h3>
                        <div class="space-y-2 text-sm">
                            @foreach($business->business_hours as $day => $hours)
                                <div class="flex justify-between">
                                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ ucfirst($day) }}</span>
                                    <span class="text-gray-600 dark:text-gray-400">
                                        @if(isset($hours['closed']) && $hours['closed'])
                                            Closed
                                        @else
                                            {{ $hours['open'] ?? 'N/A' }} - {{ $hours['close'] ?? 'N/A' }}
                                        @endif
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Payment Methods -->
                @if($business->paymentMethods->isNotEmpty())
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Payment Methods</h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach($business->paymentMethods as $method)
                                <span class="inline-flex items-center px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-md text-sm">
                                    @if($method->icon)
                                        <span class="mr-1">{{ $method->icon }}</span>
                                    @endif
                                    {{ $method->name }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Social Media -->
                @if($business->socialAccounts->isNotEmpty())
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Connect With Us</h3>
                        <div class="flex flex-wrap gap-3">
                            @foreach($business->socialAccounts as $social)
                                <a 
                                    href="{{ $social->url }}" 
                                    target="_blank" 
                                    rel="noopener"
                                    class="flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg text-gray-900 dark:text-white"
                                >
                                    <span class="text-xl">{{ $social->icon ?? '🔗' }}</span>
                                    <span class="text-sm font-medium">{{ ucfirst($social->platform) }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Map (if coordinates available) -->
                @if($business->latitude && $business->longitude)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Location</h3>
                        <div class="h-64 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                            <p class="text-gray-500 dark:text-gray-400">Map integration (lat: {{ $business->latitude }}, lng: {{ $business->longitude }})</p>
                        </div>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            @if($business->address){{ $business->address }}, @endif
                            @if($business->area){{ $business->area }}, @endif
                            {{ $business->city }}, {{ $business->state }}
                        </p>
                    </div>
                @endif
            </aside>
        </div>
    </div>
</div>

<!-- Inquiry Modal -->
<div id="inquiry-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center sticky top-0 bg-white dark:bg-gray-800">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">Send Inquiry to {{ $business->business_name }}</h3>
            <button onclick="document.getElementById('inquiry-modal').classList.add('hidden')" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="p-6">
            <form id="inquiry-form" onsubmit="submitInquiry(event)">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name *</label>
                        <input type="text" name="name" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email *</label>
                        <input type="email" name="email" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone *</label>
                        <input type="tel" name="phone" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Message *</label>
                        <textarea name="message" rows="4" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"></textarea>
                    </div>
                    
                    <button type="submit" class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                        Send Inquiry
                    </button>
                </div>
            </form>
            <div id="inquiry-success" class="hidden text-center py-8">
                <svg class="mx-auto h-16 w-16 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h4 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Inquiry Sent!</h4>
                <p class="mt-2 text-gray-600 dark:text-gray-400">The business will contact you soon.</p>
            </div>
        </div>
    </div>
</div>

<!-- Review Modal -->
<div id="review-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center sticky top-0 bg-white dark:bg-gray-800">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">Write a Review for {{ $business->business_name }}</h3>
            <button onclick="document.getElementById('review-modal').classList.add('hidden')" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="p-6">
            <form id="review-form" onsubmit="submitReview(event)">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rating *</label>
                        <div class="flex gap-2" id="star-rating">
                            @for($i = 1; $i <= 5; $i++)
                                <svg 
                                    data-rating="{{ $i }}"
                                    onclick="setRating({{ $i }})"
                                    class="w-8 h-8 cursor-pointer text-gray-300 hover:text-yellow-400 transition-colors star-icon" 
                                    fill="currentColor" 
                                    viewBox="0 0 20 20"
                                >
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                            @endfor
                        </div>
                        <input type="hidden" name="rating" id="rating-input" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                        <input type="text" name="reviewer_name" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                        <input type="email" name="reviewer_email" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Review *</label>
                        <textarea name="comment" rows="4" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"></textarea>
                    </div>
                    
                    <button type="submit" class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                        Submit Review
                    </button>
                </div>
            </form>
            <div id="review-success" class="hidden text-center py-8">
                <svg class="mx-auto h-16 w-16 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h4 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Review Submitted!</h4>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Thank you for your feedback. Your review will be published after approval.</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let selectedRating = 0;

    function setRating(rating) {
        selectedRating = rating;
        document.getElementById('rating-input').value = rating;
        
        // Update star colors
        const stars = document.querySelectorAll('.star-icon');
        stars.forEach((star, index) => {
            if (index < rating) {
                star.classList.remove('text-gray-300');
                star.classList.add('text-yellow-400');
            } else {
                star.classList.remove('text-yellow-400');
                star.classList.add('text-gray-300');
            }
        });
    }

    async function submitReview(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        
        try {
            const response = await fetch('{{ route("businesses.reviews.store", ["businessType" => $business->businessType->slug, "slug" => $business->slug]) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                form.classList.add('hidden');
                document.getElementById('review-success').classList.remove('hidden');
                setTimeout(() => {
                    document.getElementById('review-modal').classList.add('hidden');
                    location.reload();
                }, 2000);
            } else {
                alert(data.message || 'Failed to submit review');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Failed to submit review. Please try again.');
        }
    }

    async function submitInquiry(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        
        try {
            const response = await fetch('{{ route("businesses.leads.store", ["businessType" => $business->businessType->slug, "slug" => $business->slug]) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                form.classList.add('hidden');
                document.getElementById('inquiry-success').classList.remove('hidden');
                setTimeout(() => {
                    document.getElementById('inquiry-modal').classList.add('hidden');
                    form.classList.remove('hidden');
                    document.getElementById('inquiry-success').classList.add('hidden');
                    form.reset();
                }, 2000);
            } else {
                alert(data.message || 'Failed to send inquiry');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Failed to send inquiry. Please try again.');
        }
    }
</script>
@endpush
@endsection
