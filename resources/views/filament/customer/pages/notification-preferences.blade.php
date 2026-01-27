<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Info Banner --}}
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                        Manage Your Notifications
                    </h3>
                    <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                        <p>Control how and when you receive notifications. You can customize email and in-app preferences separately.</p>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Form --}}
        <form wire:submit="updatePreferences">
            {{ $this->form }}
            
            {{-- Save Button --}}
            <div class="mt-6 flex justify-end">
                <x-filament::button type="submit" 
                 wire:loading.attr="disabled"
                wire:target="updatePreferences"
                size="md">
                    <span wire:loading.remove wire:target="updatePreferences">Save Preferences</span>
                    <span wire:loading wire:target="updatePreferences">Saving...</span>
                </x-filament::button>
            </div>
        </form>
        
        {{-- Privacy Note --}}
        <div class="mt-6 bg-gray-50 border border-gray-200 dark:bg-gray-800 rounded-lg p-4">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">Privacy & Data</h4>
                    <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                        Your notification preferences are private and secure. We respect your choices and will never share your contact information with third parties without your consent.
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>