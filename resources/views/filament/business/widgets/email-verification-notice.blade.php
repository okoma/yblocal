@if($this->shouldShow())
    <x-filament-widgets::widget>
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-envelope class="h-5 w-5 text-warning-500" />
                    <span>Email Verification Required</span>
                </div>
            </x-slot>

            <x-slot name="description">
                Please verify your email address to access all features and ensure account security.
            </x-slot>

            <div class="space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    We've sent a verification email to <strong>{{ $this->getUser()->email }}</strong>. 
                    Please check your inbox and click the verification link to verify your email address.
                </p>

                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                    <x-filament::button
                        wire:click="resendVerificationEmail"
                        color="warning"
                        icon="heroicon-o-paper-airplane"
                    >
                        Resend Verification Email
                    </x-filament::button>

                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Didn't receive the email? Check your spam folder or click the button above to resend.
                    </p>
                </div>
            </div>
        </x-filament::section>
    </x-filament-widgets::widget>
@endif
