<div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <div class="bg-white shadow-sm rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="px-6 py-8 border-b border-gray-200">
            <div class="text-center">
                <h1 class="text-3xl font-bold text-gray-900">
                    List Your Business for Free
                </h1>
                <p class="mt-2 text-lg text-gray-600">
                    Reach thousands of customers looking for businesses like yours
                </p>
                
                @if($currentStep > 1)
                    <div class="mt-4 flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-sm text-gray-600">
                            Your progress is automatically saved
                        </span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Benefits -->
        <div class="px-6 py-6 bg-blue-50 border-b border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="flex items-start space-x-3">
                    <svg class="w-6 h-6 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <h3 class="font-semibold text-gray-900">100% Free</h3>
                        <p class="text-sm text-gray-600">No credit card required</p>
                    </div>
                </div>
                
                <div class="flex items-start space-x-3">
                    <svg class="w-6 h-6 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <div>
                        <h3 class="font-semibold text-gray-900">Instant Visibility</h3>
                        <p class="text-sm text-gray-600">Live in minutes</p>
                    </div>
                </div>
                
                <div class="flex items-start space-x-3">
                    <svg class="w-6 h-6 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <div>
                        <h3 class="font-semibold text-gray-900">Reach Customers</h3>
                        <p class="text-sm text-gray-600">Connect with leads</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form -->
        <div class="px-6 py-8">
            <form wire:submit="submit">
                {{ $this->form }}
            </form>
        </div>
    </div>

    <!-- Trust Indicators -->
    <div class="mt-8 text-center">
        <p class="text-sm text-gray-500">
            🔒 Your information is secure and will never be shared without your permission
        </p>
    </div>
</div>
