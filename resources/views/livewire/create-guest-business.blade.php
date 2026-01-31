<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 py-12 px-4 sm:px-6 lg:px-8">
    
    @push('styles')
    <!-- Choices.js CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
    <style>
        /* Custom Choices.js styling to match Tailwind */
        .choices__inner {
            border: 1px solid #d1d5db !important;
            border-radius: 0.5rem !important;
            padding: 0.5rem !important;
            min-height: 3rem !important;
            background-color: white !important;
        }
        .choices__input {
            background-color: transparent !important;
        }
        .choices:focus-within .choices__inner,
        .choices.is-focused .choices__inner {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
        }
        .choices__list--multiple .choices__item {
            background-color: #3b82f6 !important;
            border: none !important;
            color: white !important;
            border-radius: 0.375rem !important;
            padding: 0.25rem 0.5rem !important;
            margin: 0.25rem !important;
        }
        .choices__list--multiple .choices__item.is-highlighted {
            background-color: #2563eb !important;
        }
        .choices__button {
            border-left: 1px solid rgba(255, 255, 255, 0.3) !important;
            opacity: 1 !important;
        }
        .choices__list--dropdown .choices__item--selectable.is-highlighted {
            background-color: #dbeafe !important;
            color: #1e40af !important;
        }
        .choices[data-type*=select-multiple] .choices__button,
        .choices[data-type*=text] .choices__button {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='white'%3E%3Cpath d='M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z'/%3E%3C/svg%3E") !important;
            background-size: 12px !important;
        }
    </style>
    @endpush

    @push('scripts')
    <!-- Choices.js -->
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    
    <script>
        let categoriesChoices, paymentMethodsChoices, amenitiesChoices;
        
        document.addEventListener('livewire:init', () => {
            initializeChoices();
        });
        
        // Listen for the reinitialize event from Livewire
        Livewire.on('reinitialize-choices', () => {
            setTimeout(() => {
                destroyAndReinitialize();
            }, 200);
        });
        
        // Also reinitialize after any Livewire update
        document.addEventListener('livewire:update', () => {
            setTimeout(() => {
                const categoriesEl = document.getElementById('categories');
                if (categoriesEl && !categoriesEl.disabled && !categoriesChoices) {
                    initializeChoices();
                }
            }, 100);
        });
        
        function destroyAndReinitialize() {
            // Destroy all existing instances
            if (categoriesChoices) {
                try {
                    categoriesChoices.destroy();
                } catch (e) {
                    console.log('Categories destroy error:', e);
                }
                categoriesChoices = null;
            }
            if (paymentMethodsChoices) {
                try {
                    paymentMethodsChoices.destroy();
                } catch (e) {
                    console.log('Payment methods destroy error:', e);
                }
                paymentMethodsChoices = null;
            }
            if (amenitiesChoices) {
                try {
                    amenitiesChoices.destroy();
                } catch (e) {
                    console.log('Amenities destroy error:', e);
                }
                amenitiesChoices = null;
            }
            
            // Reinitialize
            initializeChoices();
        }
        
        function initializeChoices() {
            // Categories
            const categoriesEl = document.getElementById('categories');
            if (categoriesEl && !categoriesEl.disabled && !categoriesChoices) {
                categoriesChoices = new Choices(categoriesEl, {
                    removeItemButton: true,
                    searchEnabled: true,
                    searchPlaceholderValue: 'Search categories...',
                    placeholderValue: 'Select categories...',
                    itemSelectText: 'Click to select',
                    maxItemCount: -1,
                    shouldSort: false,
                    silent: true
                });
                
                categoriesEl.addEventListener('change', function() {
                    const values = categoriesChoices.getValue(true);
                    @this.set('categories', Array.isArray(values) ? values : (values ? [values] : []));
                });
            }
            
            // Payment Methods
            const paymentMethodsEl = document.getElementById('payment_methods');
            if (paymentMethodsEl && !paymentMethodsChoices) {
                paymentMethodsChoices = new Choices(paymentMethodsEl, {
                    removeItemButton: true,
                    searchEnabled: true,
                    searchPlaceholderValue: 'Search payment methods...',
                    placeholderValue: 'Select payment methods...',
                    itemSelectText: 'Click to select',
                    maxItemCount: -1,
                    shouldSort: false,
                    silent: true
                });
                
                paymentMethodsEl.addEventListener('change', function() {
                    const values = paymentMethodsChoices.getValue(true);
                    @this.set('payment_methods', Array.isArray(values) ? values : (values ? [values] : []));
                });
            }
            
            // Amenities
            const amenitiesEl = document.getElementById('amenities');
            if (amenitiesEl && !amenitiesChoices) {
                amenitiesChoices = new Choices(amenitiesEl, {
                    removeItemButton: true,
                    searchEnabled: true,
                    searchPlaceholderValue: 'Search amenities...',
                    placeholderValue: 'Select amenities...',
                    itemSelectText: 'Click to select',
                    maxItemCount: -1,
                    shouldSort: false,
                    silent: true
                });
                
                amenitiesEl.addEventListener('change', function() {
                    const values = amenitiesChoices.getValue(true);
                    @this.set('amenities', Array.isArray(values) ? values : (values ? [values] : []));
                });
            }
        }
    </script>
    @endpush
    
    <div class="max-w-4xl mx-auto">
        
        <!-- Header -->
        <div class="text-center mb-10">
            <h1 class="text-4xl font-bold text-gray-900 mb-3">
                List Your Business for Free
            </h1>
            <p class="text-xl text-gray-600">
                Reach thousands of customers looking for businesses like yours
            </p>
        </div>

        <!-- Progress Bar -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                @for ($i = 1; $i <= $totalSteps; $i++)
                    <div class="flex-1 {{ $i < $totalSteps ? 'mr-2' : '' }}">
                        <div class="flex items-center">
                            <div class="flex items-center justify-center w-10 h-10 rounded-full {{ $currentStep >= $i ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600' }} font-semibold">
                                {{ $i }}
                            </div>
                            @if ($i < $totalSteps)
                                <div class="flex-1 h-1 mx-2 {{ $currentStep > $i ? 'bg-blue-600' : 'bg-gray-200' }}"></div>
                            @endif
                        </div>
                        <div class="mt-2 text-xs font-medium text-center {{ $currentStep >= $i ? 'text-blue-600' : 'text-gray-500' }}">
                            @if ($i == 1) Basic Info
                            @elseif ($i == 2) Location
                            @elseif ($i == 3) Hours
                            @elseif ($i == 4) Review
                            @endif
                        </div>
                    </div>
                @endfor
            </div>
            
            <!-- Completion percentage -->
            <div class="bg-gray-200 rounded-full h-2 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-500 to-purple-600 h-full transition-all duration-500" 
                     style="width: {{ $this->getCompletionPercentage() }}%"></div>
            </div>
            <p class="text-sm text-gray-600 text-center mt-2">
                {{ $this->getCompletionPercentage() }}% Complete
            </p>
        </div>

        <!-- Messages -->
        @if (session()->has('message'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-start">
                <svg class="w-5 h-5 mr-3 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('message') }}</span>
            </div>
        @endif

        @if (session()->has('draft_saved'))
            <div class="mb-6 bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-lg flex items-start">
                <svg class="w-5 h-5 mr-3 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('draft_saved') }}</span>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-start">
                <svg class="w-5 h-5 mr-3 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Main Form Card -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            
            <!-- Benefits Banner -->
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="flex items-start space-x-3 text-white">
                        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <h3 class="font-semibold">100% Free</h3>
                            <p class="text-sm text-blue-100">No credit card required</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start space-x-3 text-white">
                        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <div>
                            <h3 class="font-semibold">Instant Visibility</h3>
                            <p class="text-sm text-blue-100">Live in minutes</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start space-x-3 text-white">
                        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <div>
                            <h3 class="font-semibold">Reach Customers</h3>
                            <p class="text-sm text-blue-100">Connect with leads</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Content -->
            <div class="px-6 py-8 md:px-10 md:py-10">
                
                <!-- Step 1: Basic Information -->
                @if ($currentStep === 1)
                    <div class="space-y-6">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 mb-2">Basic Information</h2>
                            <p class="text-gray-600">Tell us about your business</p>
                        </div>

                        <!-- Business Name -->
                        <div>
                            <label for="business_name" class="block text-sm font-medium text-gray-700 mb-2">
                                Business Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   id="business_name"
                                   wire:model.live.debounce.500ms="business_name"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                   placeholder="e.g., Okoma Technologies Ltd">
                            @error('business_name') 
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Slug (auto-generated, read-only) -->
                        <div>
                            <label for="slug" class="block text-sm font-medium text-gray-700 mb-2">
                                URL Slug
                            </label>
                            <input type="text" 
                                   id="slug"
                                   wire:model="slug"
                                   readonly
                                   class="w-full px-4 py-3 border border-gray-200 rounded-lg bg-gray-50 text-gray-600"
                                   placeholder="auto-generated-from-business-name">
                            <p class="mt-1 text-xs text-gray-500">This will be your business URL</p>
                        </div>

                        <!-- Business Type -->
                        <div>
                            <label for="business_type_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Business Type <span class="text-red-500">*</span>
                            </label>
                            <select wire:model.live="business_type_id"
                                    id="business_type_id"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                                <option value="">Select a business type...</option>
                                @foreach ($businessTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                            @error('business_type_id') 
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Categories -->
                        <div>
                            <label for="categories" class="block text-sm font-medium text-gray-700 mb-2">
                                Categories <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                @if (!$business_type_id)
                                    <div class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-500 text-center">
                                        Please select a business type first
                                    </div>
                                @else
                                    <div wire:ignore>
                                        <select id="categories"
                                                multiple
                                                class="w-full">
                                            @foreach ($availableCategories as $category)
                                                <option value="{{ $category->id }}" 
                                                        @if(in_array($category->id, $categories ?? [])) selected @endif>
                                                    {{ $category->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                                
                                <div wire:loading wire:target="business_type_id" class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center rounded-lg">
                                    <svg class="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                            </div>
                            @error('categories') 
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                                Business Description <span class="text-red-500">*</span>
                            </label>
                            <textarea wire:model="description"
                                      id="description"
                                      rows="4"
                                      maxlength="1000"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                      placeholder="Tell customers about your business, services, and what makes you unique..."></textarea>
                            <div class="mt-1 flex justify-between text-xs text-gray-500">
                                <span>Describe your business in detail</span>
                                <span>{{ strlen($description) }}/1000</span>
                            </div>
                            @error('description') 
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Payment Methods -->
                        <div wire:ignore>
                            <label for="payment_methods" class="block text-sm font-medium text-gray-700 mb-2">
                                Payment Methods Accepted
                            </label>
                            <select id="payment_methods"
                                    multiple
                                    class="w-full">
                                @foreach ($availablePaymentMethods as $method)
                                    <option value="{{ $method->id }}"
                                            @if(in_array($method->id, $payment_methods ?? [])) selected @endif>
                                        {{ $method->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Amenities -->
                        <div wire:ignore>
                            <label for="amenities" class="block text-sm font-medium text-gray-700 mb-2">
                                Amenities & Features
                            </label>
                            <select id="amenities"
                                    multiple
                                    class="w-full">
                                @foreach ($availableAmenities as $amenity)
                                    <option value="{{ $amenity->id }}"
                                            @if(in_array($amenity->id, $amenities ?? [])) selected @endif>
                                        {{ $amenity->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Years in Business -->
                        <div>
                            <label for="years_in_business" class="block text-sm font-medium text-gray-700 mb-2">
                                Years in Business <span class="text-red-500">*</span>
                            </label>
                            <input type="number" 
                                   id="years_in_business"
                                   wire:model="years_in_business"
                                   min="0"
                                   max="100"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                   placeholder="0">
                            @error('years_in_business') 
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Optional: Registration Details -->
                        <details class="border border-gray-200 rounded-lg p-4">
                            <summary class="font-medium text-gray-700 cursor-pointer">Legal Information (Optional)</summary>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <label for="registration_number" class="block text-sm font-medium text-gray-700 mb-2">
                                        CAC/RC Number
                                    </label>
                                    <input type="text" 
                                           id="registration_number"
                                           wire:model="registration_number"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                           placeholder="e.g., RC123456">
                                </div>

                                <div>
                                    <label for="entity_type" class="block text-sm font-medium text-gray-700 mb-2">
                                        Entity Type
                                    </label>
                                    <select wire:model="entity_type"
                                            id="entity_type"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                                        <option value="">Select entity type...</option>
                                        <option value="Sole Proprietorship">Sole Proprietorship</option>
                                        <option value="Partnership">Partnership</option>
                                        <option value="Limited Liability Company (LLC)">Limited Liability Company (LLC)</option>
                                        <option value="Corporation">Corporation</option>
                                        <option value="Non-Profit">Non-Profit</option>
                                    </select>
                                </div>
                            </div>
                        </details>
                    </div>
                @endif

                <!-- Step 2: Location & Contact -->
                @if ($currentStep === 2)
                    <div class="space-y-6">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 mb-2">Location & Contact</h2>
                            <p class="text-gray-600">Where can customers find you?</p>
                        </div>

                        <!-- State -->
                        <div>
                            <label for="state_location_id" class="block text-sm font-medium text-gray-700 mb-2">
                                State <span class="text-red-500">*</span>
                            </label>
                            <select wire:model.live="state_location_id"
                                    id="state_location_id"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                                <option value="">Select your state...</option>
                                @foreach ($states as $state)
                                    <option value="{{ $state->id }}">{{ $state->name }}</option>
                                @endforeach
                            </select>
                            @error('state_location_id') 
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- City -->
                        <div>
                            <label for="city_location_id" class="block text-sm font-medium text-gray-700 mb-2">
                                City <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <select wire:model.live="city_location_id"
                                        id="city_location_id"
                                        {{ !$state_location_id ? 'disabled' : '' }}
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition {{ !$state_location_id ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                                    <option value="">{{ $state_location_id ? 'Select your city...' : 'Select state first' }}</option>
                                    @foreach ($cities as $city)
                                        <option value="{{ $city->id }}">{{ $city->name }}</option>
                                    @endforeach
                                </select>
                                <div wire:loading wire:target="state_location_id" class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <svg class="animate-spin h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                            </div>
                            @error('city_location_id') 
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Address -->
                        <div>
                            <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
                                Street Address <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   id="address"
                                   wire:model="address"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                   placeholder="123 Main Street">
                            @error('address') 
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Area -->
                        <div>
                            <label for="area" class="block text-sm font-medium text-gray-700 mb-2">
                                Area/Neighborhood (Optional)
                            </label>
                            <input type="text" 
                                   id="area"
                                   wire:model="area"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                   placeholder="e.g., Ikeja, Victoria Island">
                        </div>

                        <!-- GPS Coordinates (Optional) -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="latitude" class="block text-sm font-medium text-gray-700 mb-2">
                                    Latitude (Optional)
                                </label>
                                <input type="text" 
                                       id="latitude"
                                       wire:model="latitude"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                       placeholder="6.5244">
                            </div>
                            <div>
                                <label for="longitude" class="block text-sm font-medium text-gray-700 mb-2">
                                    Longitude (Optional)
                                </label>
                                <input type="text" 
                                       id="longitude"
                                       wire:model="longitude"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                       placeholder="3.3792">
                            </div>
                        </div>

                        <hr class="my-6">

                        <!-- Phone -->
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                Phone Number <span class="text-red-500">*</span>
                            </label>
                            <input type="tel" 
                                   id="phone"
                                   wire:model="phone"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                   placeholder="+234 800 123 4567">
                            @error('phone') 
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                Email Address <span class="text-red-500">*</span>
                            </label>
                            <input type="email" 
                                   id="email"
                                   wire:model.live.debounce.500ms="email"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                   placeholder="contact@yourbusiness.com">
                            @error('email') 
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- WhatsApp -->
                        <div>
                            <label for="whatsapp" class="block text-sm font-medium text-gray-700 mb-2">
                                WhatsApp Number (Optional)
                            </label>
                            <input type="tel" 
                                   id="whatsapp"
                                   wire:model="whatsapp"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                   placeholder="+234 800 123 4567">
                        </div>

                        <!-- Website -->
                        <div>
                            <label for="website" class="block text-sm font-medium text-gray-700 mb-2">
                                Website URL (Optional)
                            </label>
                            <input type="url" 
                                   id="website"
                                   wire:model="website"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                   placeholder="https://www.yourbusiness.com">
                        </div>
                    </div>
                @endif

                <!-- Step 3: Business Hours -->
                @if ($currentStep === 3)
                    <div class="space-y-6">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 mb-2">Business Hours</h2>
                            <p class="text-gray-600">When are you open for customers?</p>
                        </div>

                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <p class="text-sm text-blue-800">
                                <strong>Note:</strong> Monday to Friday hours are required. Weekend hours are optional.
                            </p>
                        </div>

                        @php
                            $days = [
                                'monday' => 'Monday',
                                'tuesday' => 'Tuesday',
                                'wednesday' => 'Wednesday',
                                'thursday' => 'Thursday',
                                'friday' => 'Friday',
                                'saturday' => 'Saturday',
                                'sunday' => 'Sunday'
                            ];
                        @endphp

                        @foreach ($days as $key => $label)
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="text-lg font-medium text-gray-900">{{ $label }}</h3>
                                    <button type="button"
                                            wire:click="toggleDay('{{ $key }}')"
                                            class="px-4 py-2 text-sm font-medium rounded-lg transition {{ $this->{$key.'_closed'} ? 'bg-gray-200 text-gray-700' : 'bg-green-100 text-green-800' }}">
                                        {{ $this->{$key.'_closed'} ? 'Closed' : 'Open' }}
                                    </button>
                                </div>

                                @if (!$this->{$key.'_closed'})
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label for="{{ $key }}_open" class="block text-sm font-medium text-gray-700 mb-2">
                                                Opens <span class="text-red-500">*</span>
                                            </label>
                                            <input type="time" 
                                                   id="{{ $key }}_open"
                                                   wire:model="{{ $key }}_open"
                                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                                            @error($key.'_open') 
                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="{{ $key }}_close" class="block text-sm font-medium text-gray-700 mb-2">
                                                Closes <span class="text-red-500">*</span>
                                            </label>
                                            <input type="time" 
                                                   id="{{ $key }}_close"
                                                   wire:model="{{ $key }}_close"
                                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                                            @error($key.'_close') 
                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                <!-- Step 4: Review & Submit -->
                @if ($currentStep === 4)
                    <div class="space-y-6">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 mb-2">Review & Submit</h2>
                            <p class="text-gray-600">Almost done! Review your information</p>
                        </div>

                        <div class="bg-gradient-to-r from-green-50 to-blue-50 border border-green-200 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">What happens next?</h3>
                            <ul class="space-y-2 text-sm text-gray-700">
                                <li class="flex items-start">
                                    <svg class="w-5 h-5 text-green-600 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Complete <strong>authentication</strong> to verify your account
                                </li>
                                <li class="flex items-start">
                                    <svg class="w-5 h-5 text-green-600 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Your listing will be <strong>published and live</strong> immediately!
                                </li>
                                <li class="flex items-start">
                                    <svg class="w-5 h-5 text-green-600 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Start receiving <strong>customer inquiries immediately</strong>
                                </li>
                            </ul>
                        </div>

                        <!-- Summary -->
                        <div class="border border-gray-200 rounded-lg p-6 space-y-4">
                            <h3 class="font-semibold text-gray-900">Your Business Listing Summary</h3>
                            
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p class="text-gray-500">Business Name</p>
                                    <p class="font-medium text-gray-900">{{ $business_name }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Email</p>
                                    <p class="font-medium text-gray-900">{{ $email }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Location</p>
                                    <p class="font-medium text-gray-900">{{ $city }}, {{ $state }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Phone</p>
                                    <p class="font-medium text-gray-900">{{ $phone }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Authentication Section -->
                        <div class="mb-8 pb-8 border-b-2 border-gray-200">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-lg font-semibold text-gray-900">Account Authentication</h3>
                                <div class="flex space-x-2">
                                    <button type="button"
                                            wire:click="$set('has_account', false)"
                                            class="px-4 py-2 rounded-lg font-medium transition {{ !$has_account ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                                        Create Account
                                    </button>
                                    <button type="button"
                                            wire:click="$set('has_account', true)"
                                            class="px-4 py-2 rounded-lg font-medium transition {{ $has_account ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                                        I Have Account
                                    </button>
                                </div>
                            </div>

                            <!-- Create Account Mode -->
                            @if (!$has_account)
                                <div class="space-y-4">
                                    <!-- Full Name -->
                                    <div>
                                        <label for="full_name" class="block text-sm font-medium text-gray-700 mb-2">
                                            Full Name <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text"
                                               id="full_name"
                                               wire:model="full_name"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                               placeholder="Your full name">
                                        @error('full_name')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Email -->
                                    <div>
                                        <label for="auth_email" class="block text-sm font-medium text-gray-700 mb-2">
                                            Email <span class="text-red-500">*</span>
                                        </label>
                                        <input type="email"
                                               id="auth_email"
                                               wire:model="auth_email"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                               placeholder="your@email.com">
                                        @error('auth_email')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Email Verification -->
                                    @if (!$code_verified)
                                        <div>
                                            <button type="button"
                                                    wire:click="sendVerificationCode"
                                                    wire:loading.attr="disabled"
                                                    class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition disabled:opacity-50">
                                                <span wire:loading.remove wire:target="sendVerificationCode">Send Verification Code</span>
                                                <span wire:loading wire:target="sendVerificationCode">
                                                    <svg class="animate-spin h-4 w-4 inline mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                    Sending...
                                                </span>
                                            </button>
                                        </div>

                                        @if ($code_sent)
                                            <div class="flex space-x-2">
                                                <input type="text"
                                                       wire:model="verification_code"
                                                       maxlength="6"
                                                       placeholder="000000"
                                                       class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition text-center font-mono text-lg"
                                                       inputmode="numeric">
                                                <button type="button"
                                                        wire:click="verifyCode"
                                                        wire:loading.attr="disabled"
                                                        class="px-6 py-3 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition disabled:opacity-50">
                                                    <span wire:loading.remove wire:target="verifyCode">Verify</span>
                                                    <span wire:loading wire:target="verifyCode">
                                                        <svg class="animate-spin h-4 w-4 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                    </span>
                                                </button>
                                            </div>
                                            <p class="text-xs text-gray-500">Enter the 6-digit code sent to your email</p>
                                        @endif
                                    @else
                                        <div class="flex items-center space-x-2 p-3 bg-green-50 border border-green-200 rounded-lg">
                                            <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            <span class="text-green-800 font-medium">Email verified! ✓</span>
                                        </div>
                                    @endif
                                </div>
                            @else
                                <!-- Login Mode -->
                                <div class="space-y-4">
                                    <!-- Email -->
                                    <div>
                                        <label for="auth_email" class="block text-sm font-medium text-gray-700 mb-2">
                                            Email <span class="text-red-500">*</span>
                                        </label>
                                        <input type="email"
                                               id="auth_email"
                                               wire:model="auth_email"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                               placeholder="your@email.com">
                                        @error('auth_email')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Password -->
                                    <div>
                                        <label for="auth_password" class="block text-sm font-medium text-gray-700 mb-2">
                                            Password <span class="text-red-500">*</span>
                                        </label>
                                        <input type="password"
                                               id="auth_password"
                                               wire:model="auth_password"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                               placeholder="••••••••">
                                        @error('auth_password')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Terms & Conditions -->
                        <div class="border border-gray-200 rounded-lg p-6">
                            <label class="flex items-start cursor-pointer">
                                <input type="checkbox" 
                                       wire:model="terms"
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 mt-1">
                                <span class="ml-3 text-sm text-gray-700">
                                    I agree to the <a href="#" class="text-blue-600 hover:underline">Terms of Service</a> and <a href="#" class="text-blue-600 hover:underline">Privacy Policy</a>
                                </span>
                            </label>
                            @error('terms') 
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                @endif

                <!-- Navigation Buttons -->
                <div class="flex items-center justify-between mt-8 pt-6 border-t border-gray-200">
                    @if ($currentStep > 1)
                        <button type="button"
                                wire:click="previousStep"
                                wire:loading.attr="disabled"
                                class="px-6 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition disabled:opacity-50 disabled:cursor-not-allowed">
                            <span wire:loading.remove wire:target="previousStep">← Previous</span>
                            <span wire:loading wire:target="previousStep">
                                <svg class="animate-spin h-4 w-4 inline mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Loading...
                            </span>
                        </button>
                    @else
                        <div></div>
                    @endif

                    @if ($currentStep < $totalSteps)
                        <button type="button"
                                wire:click="nextStep"
                                wire:loading.attr="disabled"
                                class="px-6 py-3 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition shadow-lg disabled:opacity-50 disabled:cursor-not-allowed">
                            <span wire:loading.remove wire:target="nextStep">Continue →</span>
                            <span wire:loading wire:target="nextStep">
                                <svg class="animate-spin h-4 w-4 inline mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Processing...
                            </span>
                        </button>
                    @else
                        <button type="button"
                                wire:click="submit"
                                wire:loading.attr="disabled"
                                class="px-8 py-3 text-sm font-medium text-white bg-gradient-to-r from-green-600 to-blue-600 rounded-lg hover:from-green-700 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition shadow-lg disabled:opacity-50 disabled:cursor-not-allowed">
                            <span wire:loading.remove wire:target="submit">🚀 Create Business</span>
                            <span wire:loading wire:target="submit">
                                <svg class="animate-spin h-4 w-4 inline mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Creating...
                            </span>
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Trust Indicators -->
        <div class="mt-8 text-center">
            <p class="text-sm text-gray-500 flex items-center justify-center">
                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                </svg>
                Your information is secure and will never be shared without your permission
            </p>
        </div>
    </div>
</div>