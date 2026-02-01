<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Header with Search & Filter Toggle -->
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 sticky top-0 z-30">
        <div class="w-full px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center gap-4">
                <!-- Filter Toggle Button -->
                <button 
                    wire:click="toggleFilters"
                    type="button"
                    class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors relative shrink-0"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    <span class="font-medium hidden sm:inline">Filters</span>
                    @if($this->activeFiltersCount > 0)
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs w-5 h-5 flex items-center justify-center rounded-full">
                            {{ $this->activeFiltersCount }}
                        </span>
                    @endif
                </button>

                <!-- Search Bar -->
                <div class="flex-1">
                    <input 
                        type="text" 
                        wire:model.live.debounce.500ms="search"
                        placeholder="Search businesses..."
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                    >
                </div>

                <!-- Sort Dropdown -->
                <select 
                    wire:model.live="sort"
                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white shrink-0 hidden sm:block"
                >
                    <option value="relevance">Most Relevant</option>
                    <option value="rating">Highest Rated</option>
                    <option value="newest">Newest</option>
                    <option value="name">Name (A-Z)</option>
                </select>
            </div>

            <!-- Active Filters Pills -->
            @if($this->activeFiltersCount > 0)
                <div class="mt-3 flex flex-wrap gap-2">
                    @if(!empty($businessType))
                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-full text-sm">
                            Type: {{ $businessType }}
                            <button wire:click="clearFilter('businessType')" class="hover:text-blue-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </span>
                    @endif

                    @if(!empty($category))
                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-full text-sm">
                            Category: {{ $category }}
                            <button wire:click="clearFilter('category')" class="hover:text-green-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </span>
                    @endif

                    @if(!empty($state))
                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 rounded-full text-sm">
                            State: {{ $state }}
                            <button wire:click="clearFilter('state')" class="hover:text-purple-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </span>
                    @endif

                    @if(!empty($city))
                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 rounded-full text-sm">
                            City: {{ $city }}
                            <button wire:click="clearFilter('city')" class="hover:text-purple-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </span>
                    @endif

                    @if(!empty($rating))
                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 rounded-full text-sm">
                            {{ $rating }}+ Stars
                            <button wire:click="clearFilter('rating')" class="hover:text-yellow-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </span>
                    @endif

                    @if($verified)
                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-full text-sm">
                            Verified Only
                            <button wire:click="clearFilter('verified')" class="hover:text-blue-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </span>
                    @endif

                    @if($premium)
                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 rounded-full text-sm">
                            Premium Only
                            <button wire:click="clearFilter('premium')" class="hover:text-yellow-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </span>
                    @endif

                    @if($openNow)
                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-full text-sm">
                            Open Now
                            <button wire:click="clearFilter('openNow')" class="hover:text-green-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </span>
                    @endif

                    <button 
                        wire:click="clearFilters"
                        class="text-sm text-red-600 dark:text-red-400 hover:underline"
                    >
                        Clear All
                    </button>
                </div>
            @endif
        </div>
    </div>

    <!-- Main Content: Split View (Listings Left, Map Right) -->
    <div class="flex flex-col lg:flex-row h-[calc(100vh-180px)]">
        <!-- Left Side: Listings -->
        <div class="w-full lg:w-1/2 overflow-y-auto bg-white dark:bg-gray-900">
            <div class="p-4 sm:p-6">
                <!-- Results Header -->
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        @if($contextLocation)
                            Businesses in {{ $contextLocation->name }}
                        @elseif($contextCategory)
                            {{ $contextCategory->name }}
                        @elseif($contextBusinessType)
                            {{ $contextBusinessType->name }}
                        @elseif($search)
                            Search Results for "{{ $search }}"
                        @else
                            Discover Businesses
                        @endif
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">
                        <span wire:loading.remove wire:target="search,sort,businessType,category,state,city,rating,verified,premium,openNow">
                            {{ $this->businesses->total() }} businesses found
                        </span>
                        <span wire:loading wire:target="search,sort,businessType,category,state,city,rating,verified,premium,openNow" class="text-blue-600">
                            Searching...
                        </span>
                    </p>
                </div>

                <!-- Loading Overlay -->
                <div wire:loading.delay wire:target="search,sort,businessType,category,state,city,rating,verified,premium,openNow" class="fixed inset-0 bg-black bg-opacity-20 z-20 flex items-center justify-center lg:left-0 lg:right-1/2">
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-xl">
                        <svg class="animate-spin h-8 w-8 text-blue-600 mx-auto" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="mt-2 text-gray-600 dark:text-gray-400">Loading...</p>
                    </div>
                </div>

                <!-- Business List -->
                @if($this->businesses->count() > 0)
                    <div class="space-y-4 mb-8">
                        @foreach($this->businesses as $business)
                            <x-business-card :business="$business" />
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    <div class="mt-8">
                        {{ $this->businesses->links() }}
                    </div>
                @else
                    <!-- Empty State -->
                    <div class="text-center py-12">
                        <svg class="mx-auto h-24 w-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No businesses found</h3>
                        <p class="mt-2 text-gray-600 dark:text-gray-400">Try adjusting your filters or search terms.</p>
                        @if($this->activeFiltersCount > 0)
                            <button 
                                wire:click="clearFilters"
                                class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                            >
                                Clear All Filters
                            </button>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <!-- Right Side: Map (Desktop Only) -->
        <div class="hidden lg:block lg:w-1/2 bg-gray-200 dark:bg-gray-700 sticky top-[180px] h-[calc(100vh-180px)]">
            <div id="business-map" class="w-full h-full">
                <!-- Map will be initialized here -->
                <div class="flex items-center justify-center h-full text-gray-500 dark:text-gray-400">
                    <div class="text-center">
                        <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                        </svg>
                        <p class="text-lg font-medium">Map View</p>
                        <p class="text-sm mt-1">Businesses will appear on the map</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Canvas (Offcanvas Drawer) -->
    @if($showFilters)
        <div 
            class="fixed inset-0 z-50"
            x-data="{ closing: false }"
            x-init="document.body.style.overflow = 'hidden'"
            @click.self="closing = true; setTimeout(() => $wire.set('showFilters', false), 300)"
        >
            <!-- Backdrop -->
            <div 
                class="fixed inset-0 bg-black transition-opacity duration-300"
                :class="closing ? 'bg-opacity-0' : 'bg-opacity-50'"
            ></div>

            <!-- Drawer -->
            <div 
                class="fixed right-0 top-0 h-full w-full sm:w-96 bg-white dark:bg-gray-800 shadow-xl overflow-y-auto transform transition-transform duration-300"
                :class="closing ? 'translate-x-full' : 'translate-x-0'"
                @click.stop
            >
                <!-- Header -->
                <div class="sticky top-0 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex items-center justify-between z-10">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Filters</h2>
                    <button 
                        wire:click="toggleFilters"
                        class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Filter Content -->
                <div class="p-6">
                    <!-- Business Type Filter -->
                    @if($this->businessTypes->isNotEmpty())
                        <div class="mb-6 pb-6 border-b border-gray-200 dark:border-gray-700">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Business Type</label>
                            <select 
                                wire:model.live="businessType"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                            >
                                <option value="">-- Select Type --</option>
                                @foreach($this->businessTypes as $type)
                                    <option value="{{ $type->slug }}">
                                        {{ $type->icon ?? '' }} {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <!-- Category Filter -->
                    @if($this->categories->isNotEmpty())
                        <div class="mb-6 pb-6 border-b border-gray-200 dark:border-gray-700">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Categories</label>
                            <select 
                                wire:model.live="category"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                            >
                                <option value="">-- Select Category --</option>
                                @foreach($this->categories as $cat)
                                    <option value="{{ $cat->slug }}">
                                        {{ $cat->icon ?? '' }} {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <!-- Location Filter -->
                    <div class="mb-6 pb-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Location</h3>
                        
                        <!-- State Filter -->
                        @if($this->states->isNotEmpty())
                            <div class="mb-4">
                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-2">State</label>
                                <select 
                                    wire:model.live="state"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="">All States</option>
                                    @foreach($this->states as $stateOption)
                                        <option value="{{ $stateOption->slug }}">{{ $stateOption->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <!-- City Filter -->
                        @if($this->cities->isNotEmpty())
                            <div>
                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-2">City</label>
                                <select 
                                    wire:model.live="city"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="">All Cities</option>
                                    @foreach($this->cities as $cityOption)
                                        <option value="{{ $cityOption->slug }}">{{ $cityOption->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>

                    <!-- Rating Filter -->
                    <div class="mb-6 pb-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Minimum Rating</h3>
                        <div class="space-y-2">
                            @foreach([5, 4, 3, 2, 1] as $ratingOption)
                                <label class="flex items-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 p-2 rounded-md transition-colors">
                                    <input 
                                        type="checkbox" 
                                        wire:model.live="rating"
                                        value="{{ $ratingOption }}"
                                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                    >
                                    <span class="ml-2 flex items-center">
                                        @for($i = 1; $i <= $ratingOption; $i++)
                                            <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                            </svg>
                                        @endfor
                                        <span class="ml-1 text-sm text-gray-700 dark:text-gray-300">& Up</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Price Range Filter -->
                    <div class="mb-6 pb-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Price Tier</h3>
                        <div class="space-y-2">
                            <label class="flex items-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 p-2 rounded-md transition-colors">
                                <input 
                                    type="checkbox" 
                                    wire:model.live="priceTier"
                                    value="budget"
                                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                >
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">₦ Budget</span>
                            </label>
                            
                            <label class="flex items-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 p-2 rounded-md transition-colors">
                                <input 
                                    type="checkbox" 
                                    wire:model.live="priceTier"
                                    value="affordable"
                                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                >
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">₦₦ Affordable</span>
                            </label>
                            
                            <label class="flex items-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 p-2 rounded-md transition-colors">
                                <input 
                                    type="checkbox" 
                                    wire:model.live="priceTier"
                                    value="premium"
                                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                >
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">₦₦₦ Premium</span>
                            </label>
                            
                            <label class="flex items-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 p-2 rounded-md transition-colors">
                                <input 
                                    type="checkbox" 
                                    wire:model.live="priceTier"
                                    value="luxury"
                                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                >
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">₦₦₦₦ Luxury</span>
                            </label>
                        </div>
                    </div>

                    <!-- Amenities Filter -->
                    <div class="mb-6 pb-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Amenities</h3>
                        <div class="space-y-2">
                            @foreach($this->amenities as $amenity)
                                <label class="flex items-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 p-2 rounded-md transition-colors">
                                    <input 
                                        type="checkbox" 
                                        wire:model.live="amenities"
                                        value="{{ $amenity->id }}"
                                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                    >
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $amenity->name }}</span>
                                    @if($amenity->icon)
                                        <span class="ml-1 text-lg">{{ $amenity->icon }}</span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Special Features -->
                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Features</h3>
                        <div class="space-y-2">
                            <label class="flex items-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 p-2 rounded-md transition-colors">
                                <input 
                                    type="checkbox" 
                                    wire:model.live="verified"
                                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                >
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Verified Only</span>
                            </label>
                            
                            <label class="flex items-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 p-2 rounded-md transition-colors">
                                <input 
                                    type="checkbox" 
                                    wire:model.live="premium"
                                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                >
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Premium Only</span>
                            </label>
                            
                            <label class="flex items-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 p-2 rounded-md transition-colors">
                                <input 
                                    type="checkbox" 
                                    wire:model.live="openNow"
                                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                >
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Open Now</span>
                            </label>
                        </div>
                    </div>

                    <!-- Clear All Button -->
                    @if($this->activeFiltersCount > 0)
                        <button 
                            wire:click="clearFilters"
                            class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600 text-sm font-medium transition-colors"
                        >
                            Clear All Filters
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <script>
            // Clean up body overflow when component is destroyed or drawer closes
            document.addEventListener('livewire:navigated', () => {
                if (!@js($showFilters)) {
                    document.body.style.overflow = '';
                }
            });
        </script>
    @endif
</div>