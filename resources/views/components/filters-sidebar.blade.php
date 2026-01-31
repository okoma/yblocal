
//resources/views/components/filters-sidebar.blade.php
@props(['businessTypes', 'categories', 'states', 'cities' => [], 'activeFilters' => []])

<div>
    <form action="{{ url()->current() }}" method="GET" id="filter-form">
        <!-- Preserve search query if present -->
        @if(request('q'))
            <input type="hidden" name="q" value="{{ request('q') }}">
        @endif

        <!-- Business Type Filter -->
        @if(isset($businessTypes) && $businessTypes->isNotEmpty())
            <div class="mb-6">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Business Type</h3>
                <div class="space-y-2">
                    @foreach($businessTypes as $type)
                        <label class="flex items-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 p-2 rounded-md">
                            <input 
                                type="radio" 
                                name="business_type" 
                                value="{{ $type->slug }}"
                                {{ request('business_type') === $type->slug ? 'checked' : '' }}
                                class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                                onchange="this.form.submit()"
                            >
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                @if($type->icon)
                                    <span>{{ $type->icon }}</span>
                                @endif
                                {{ $type->name }}
                            </span>
                        </label>
                    @endforeach
                    @if(request('business_type'))
                        <button 
                            type="button" 
                            onclick="document.querySelector('input[name=business_type]:checked').checked = false; this.form.submit();"
                            class="text-xs text-blue-600 dark:text-blue-400 hover:underline"
                        >
                            Clear
                        </button>
                    @endif
                </div>
            </div>
        @endif

        <!-- Category Filter -->
        @if(isset($categories) && $categories->isNotEmpty())
            <div class="mb-6 pb-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Categories</h3>
                <div class="space-y-2 max-h-64 overflow-y-auto">
                    @foreach($categories as $category)
                        <label class="flex items-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 p-2 rounded-md">
                            <input 
                                type="checkbox" 
                                name="category" 
                                value="{{ $category->slug }}"
                                {{ request('category') === $category->slug ? 'checked' : '' }}
                                class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                onchange="this.form.submit()"
                            >
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                @if($category->icon)
                                    <span>{{ $category->icon }}</span>
                                @endif
                                {{ $category->name }}
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Location Filter -->
        <div class="mb-6 pb-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Location</h3>
            
            <!-- State Filter -->
            @if(isset($states) && $states->isNotEmpty())
                <div class="mb-4">
                    <label class="block text-xs text-gray-600 dark:text-gray-400 mb-2">State</label>
                    <select 
                        name="state" 
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm dark:bg-gray-700 dark:text-white"
                        onchange="loadCitiesByState(this.value)"
                    >
                        <option value="">All States</option>
                        @foreach($states as $state)
                            <option 
                                value="{{ $state->slug }}" 
                                {{ request('state') === $state->slug ? 'selected' : '' }}
                            >
                                {{ $state->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <!-- City Filter -->
            <div id="city-filter-container" class="{{ isset($cities) && $cities->isNotEmpty() ? '' : 'hidden' }}">
                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-2">City</label>
                <select 
                    name="city" 
                    id="city-select"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm dark:bg-gray-700 dark:text-white"
                    onchange="this.form.submit()"
                >
                    <option value="">All Cities</option>
                    @if(isset($cities))
                        @foreach($cities as $city)
                            <option 
                                value="{{ $city->slug }}" 
                                {{ request('city') === $city->slug ? 'selected' : '' }}
                            >
                                {{ $city->name }}
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>
        </div>

        <!-- Rating Filter -->
        <div class="mb-6 pb-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Minimum Rating</h3>
            <div class="space-y-2">
                @foreach([5, 4, 3, 2, 1] as $rating)
                    <label class="flex items-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 p-2 rounded-md">
                        <input 
                            type="radio" 
                            name="rating" 
                            value="{{ $rating }}"
                            {{ request('rating') == $rating ? 'checked' : '' }}
                            class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                            onchange="this.form.submit()"
                        >
                        <span class="ml-2 flex items-center">
                            @for($i = 1; $i <= $rating; $i++)
                                <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                            @endfor
                            <span class="ml-1 text-sm text-gray-700 dark:text-gray-300">& Up</span>
                        </span>
                    </label>
                @endforeach
                @if(request('rating'))
                    <button 
                        type="button" 
                        onclick="document.querySelector('input[name=rating]:checked').checked = false; this.form.submit();"
                        class="text-xs text-blue-600 dark:text-blue-400 hover:underline"
                    >
                        Clear
                    </button>
                @endif
            </div>
        </div>

        <!-- Special Features -->
        <div class="mb-6">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Features</h3>
            <div class="space-y-2">
                <label class="flex items-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 p-2 rounded-md">
                    <input 
                        type="checkbox" 
                        name="verified" 
                        value="1"
                        {{ request('verified') ? 'checked' : '' }}
                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                        onchange="this.form.submit()"
                    >
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Verified Only</span>
                </label>
                
                <label class="flex items-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 p-2 rounded-md">
                    <input 
                        type="checkbox" 
                        name="premium" 
                        value="1"
                        {{ request('premium') ? 'checked' : '' }}
                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                        onchange="this.form.submit()"
                    >
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Premium Only</span>
                </label>
                
                <label class="flex items-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 p-2 rounded-md">
                    <input 
                        type="checkbox" 
                        name="open_now" 
                        value="1"
                        {{ request('open_now') ? 'checked' : '' }}
                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                        onchange="this.form.submit()"
                    >
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Open Now</span>
                </label>
            </div>
        </div>

        <!-- Clear All Filters -->
        @if(request()->except(['page', 'per_page'])->count() > 0)
            <button 
                type="button" 
                onclick="window.location.href='{{ url()->current() }}'"
                class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600 text-sm font-medium"
            >
                Clear All Filters
            </button>
        @endif
    </form>
</div>

@push('scripts')
<script>
    async function loadCitiesByState(stateSlug) {
        const cityContainer = document.getElementById('city-filter-container');
        const citySelect = document.getElementById('city-select');
        
        if (!stateSlug) {
            cityContainer.classList.add('hidden');
            citySelect.innerHTML = '<option value="">All Cities</option>';
            document.getElementById('filter-form').submit();
            return;
        }
        
        try {
            const response = await fetch(`/api/locations/states/${stateSlug}/cities`);
            const cities = await response.json();
            
            citySelect.innerHTML = '<option value="">All Cities</option>';
            cities.forEach(city => {
                const option = document.createElement('option');
                option.value = city.slug;
                option.textContent = city.name;
                citySelect.appendChild(option);
            });
            
            cityContainer.classList.remove('hidden');
            document.getElementById('filter-form').submit();
        } catch (error) {
            console.error('Failed to load cities:', error);
        }
    }
</script>
@endpush
