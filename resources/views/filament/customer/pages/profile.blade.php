<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Profile Information Form --}}
        <x-filament::card>
            <form wire:submit="updateProfile">
                {{ $this->profileForm }}
                
                <div class="mt-6 flex justify-end">
                    <x-filament::button 
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="updateProfile"
                    >
                        <span wire:loading.remove wire:target="updateProfile">Save Profile</span>
                        <span wire:loading wire:target="updateProfile">Save Profile</span>
                    </x-filament::button>
                </div>
            </form>
        </x-filament::card>

        {{-- Password Change Form --}}
        <x-filament::card>
            <form wire:submit="updatePassword">
                {{ $this->passwordForm }}
                
                <div class="mt-6 flex justify-end">
                    <x-filament::button 
                        type="submit"
                        color="warning"
                        wire:loading.attr="disabled"
                        wire:target="updatePassword"
                    >
                        <span wire:loading.remove wire:target="updatePassword">Update Password</span>
                        <span wire:loading wire:target="updatePassword">Update Password</span>
                    </x-filament::button>
                </div>
            </form>
        </x-filament::card>

        {{-- Danger Zone --}}
        <x-filament::card>
            <x-filament::section>
                <x-slot name="heading">
                    Danger Zone
                </x-slot>

                <x-slot name="description">
                    Permanently delete your account and all associated data.
                </x-slot>

                <div class="space-y-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Once your account is deleted, all of your resources and data will be permanently deleted. 
                        Before deleting your account, please download any data or information that you wish to retain.
                    </p>

                    <x-filament::button 
                        wire:click="deleteAccount" 
                        color="danger"
                        outlined
                    >
                        Delete Account
                    </x-filament::button>
                </div>
            </x-filament::section>
        </x-filament::card>
    </div>
</x-filament-panels::page>
