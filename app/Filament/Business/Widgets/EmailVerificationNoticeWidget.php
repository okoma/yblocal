<?php

namespace App\Filament\Business\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class EmailVerificationNoticeWidget extends Widget
{
    protected static string $view = 'filament.business.widgets.email-verification-notice';

    protected int | string | array $columnSpan = 'full';

    // Poll every 5 seconds to check if email is verified
    protected static ?string $pollingInterval = '5s';

    public function getUser()
    {
        return filament()->auth()->user();
    }

    public function resendVerificationEmail(): void
    {
        /** @var MustVerifyEmail $user */
        $user = filament()->auth()->user();

        if ($user && !$user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();

            \Filament\Notifications\Notification::make()
                ->title('Verification email has been sent.')
                ->success()
                ->send();
        }
    }

    public function shouldShow(): bool
    {
        /** @var MustVerifyEmail $user */
        $user = filament()->auth()->user();
        return $user && !$user->hasVerifiedEmail();
    }
}
