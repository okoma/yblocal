<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 py-12 px-4 sm:px-6 lg:px-8">
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
                        @if ($business_type_id && count($availableCategories) > 0)
                            <div>
                                <label for="categories" class="block text-sm font-medium text-gray-700 mb-2">
                                    Categories <span class="text-red-500">*</span>
                                </label>
                                <select wire:model="categories"
                                        id="categories"
                                        multiple
                                        size="5"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                                    @foreach ($availableCategories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Hold Ctrl (Cmd on Mac) to select multiple categories</p>
                                @error('categories') 
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif

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
                        <div>
                            <label for="payment_methods" class="block text-sm font-medium text-gray-700 mb-2">
                                Payment Methods Accepted
                            </label>
                            <select wire:model="payment_methods"
                                    id="payment_methods"
                                    multiple
                                    size="5"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                                @foreach ($availablePaymentMethods as $method)
                                    <option value="{{ $method->id }}">{{ $method->name }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Hold Ctrl (Cmd on Mac) to select multiple payment methods</p>
                        </div>

                        <!-- Amenities -->
                        <div>
                            <label for="amenities" class="block text-sm font-medium text-gray-700 mb-2">
                                Amenities & Features
                            </label>
                            <select wire:model="amenities"
                                    id="amenities"
                                    multiple
                                    size="5"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                                @foreach ($availableAmenities as $amenity)
                                    <option value="{{ $amenity->id }}">{{ $amenity->name }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Hold Ctrl (Cmd on Mac) to select multiple amenities</p>
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
                            <select wire:model.live="city_location_id"
                                    id="city_location_id"
                                    {{ !$state_location_id ? 'disabled' : '' }}
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition {{ !$state_location_id ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                                <option value="">{{ $state_location_id ? 'Select your city...' : 'Select state first' }}</option>
                                @foreach ($cities as $city)
                                    <option value="{{ $city->id }}">{{ $city->name }}</option>
                                @endforeach
                            </select>
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
                                    Your business listing will be created as a <strong>draft</strong>
                                </li>
                                <li class="flex items-start">
                                    <svg class="w-5 h-5 text-green-600 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    You'll be redirected to <strong>login or create an account</strong>
                                </li>
                                <li class="flex items-start">
                                    <svg class="w-5 h-5 text-green-600 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    After login, your listing will be <strong>published</strong> and live!
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
                                class="px-6 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                            ← Previous
                        </button>
                    @else
                        <div></div>
                    @endif

                    @if ($currentStep < $totalSteps)
                        <button type="button"
                                wire:click="nextStep"
                                class="px-6 py-3 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition shadow-lg">
                            Continue →
                        </button>
                    @else
                        <button type="button"
                                wire:click="submit"
                                class="px-8 py-3 text-sm font-medium text-white bg-gradient-to-r from-green-600 to-blue-600 rounded-lg hover:from-green-700 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition shadow-lg">
                            🚀 Create Business Listing
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