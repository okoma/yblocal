<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse ($this->businesses as $business)
            <div 
                wire:click="selectBusiness({{ $business->id }})"
                class="group relative bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 cursor-pointer transition-all duration-200 hover:shadow-xl hover:border-primary-300 dark:hover:border-primary-600 hover:-translate-y-1 flex flex-col"
            >
                {{-- Logo --}}
                <div class="flex justify-center mb-4">
                    @if($business->logo)
                        <img 
                            src="{{ asset('storage/' . $business->logo) }}" 
                            alt="{{ $business->name }}"
                            class="w-20 h-20 rounded-xl object-cover border-2 border-gray-200 dark:border-gray-700 group-hover:border-primary-300 dark:group-hover:border-primary-600 transition-colors"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                        />
                        <div class="w-20 h-20 rounded-xl bg-gradient-to-br from-primary-100 to-primary-200 dark:from-primary-900 dark:to-primary-800 flex items-center justify-center border-2 border-gray-200 dark:border-gray-700 group-hover:border-primary-300 dark:group-hover:border-primary-600 transition-colors hidden">
                            <x-filament::icon
                                icon="heroicon-o-building-storefront"
                                class="h-10 w-10 text-primary-600 dark:text-primary-400"
                            />
                        </div>
                    @else
                        <div class="w-20 h-20 rounded-xl bg-gradient-to-br from-primary-100 to-primary-200 dark:from-primary-900 dark:to-primary-800 flex items-center justify-center border-2 border-gray-200 dark:border-gray-700 group-hover:border-primary-300 dark:group-hover:border-primary-600 transition-colors">
                            <x-filament::icon
                                icon="heroicon-o-building-storefront"
                                class="h-10 w-10 text-primary-600 dark:text-primary-400"
                            />
                        </div>
                    @endif
                </div>

                {{-- Business Name --}}
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors text-center mb-3 line-clamp-2">
                    {{ $business->name }}
                </h3>

                {{-- Status Badges --}}
                <div class="flex items-center justify-center gap-2 flex-wrap mb-4">
                    @if($business->is_verified)
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Verified
                        </span>
                    @endif
                    
                    @php
                        $statusClasses = [
                            'active' => 'bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400',
                            'pending_review' => 'bg-warning-100 text-warning-700 dark:bg-warning-900/30 dark:text-warning-400',
                            'draft' => 'bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400',
                            'suspended' => 'bg-danger-100 text-danger-700 dark:bg-danger-900/30 dark:text-danger-400',
                            'closed' => 'bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400',
                        ];
                        $statusLabels = [
                            'active' => 'Active',
                            'pending_review' => 'Pending',
                            'draft' => 'Draft',
                            'suspended' => 'Suspended',
                            'closed' => 'Closed',
                        ];
                        $statusClass = $statusClasses[$business->status] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400';
                        $statusLabel = $statusLabels[$business->status] ?? ucfirst($business->status);
                    @endphp
                    
                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium {{ $statusClass }}">
                        {{ $statusLabel }}
                    </span>
                </div>

                {{-- Click hint --}}
                <div class="mt-auto pt-4 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-xs text-gray-500 dark:text-gray-400 text-center flex items-center justify-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
                        </svg>
                        Click to select
                    </p>
                </div>
            </div>
        @empty
            <div class="col-span-full">
                {{-- Custom styled empty state --}}
                <div class="flex flex-col items-center justify-center py-16 px-4 text-center bg-gradient-to-br from-primary-50 to-primary-100 dark:from-gray-800 dark:to-gray-900 rounded-2xl border-2 border-dashed border-primary-200 dark:border-gray-700">
                    <div class="mb-6">
                        <div class="w-24 h-24 rounded-full bg-primary-500 dark:bg-primary-600 flex items-center justify-center shadow-lg">
                            <x-filament::icon
                                icon="heroicon-o-building-storefront"
                                class="h-12 w-12 text-white"
                            />
                        </div>
                    </div>
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-3">
                        Get Started
                    </h2>
                    <p class="text-lg text-gray-600 dark:text-gray-300 mb-8 max-w-lg leading-relaxed">
                        Create your first business to start managing your listings, leads, reviews, and analytics all in one place.
                    </p>
                    <x-filament::button
                        tag="a"
                        href="{{ \App\Filament\Business\Resources\BusinessResource::getUrl('create') }}"
                        color="primary"
                        size="xl"
                        icon="heroicon-o-plus"
                        class="shadow-lg hover:shadow-xl transition-shadow"
                    >
                        Create Your First Business
                    </x-filament::button>
                </div>
            </div>
        @endforelse
    </div>
</x-filament-panels::page>
