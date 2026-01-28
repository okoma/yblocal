<?php

namespace App\Filament\Business\Pages\Auth;

use Filament\Pages\Auth\EmailVerification\EmailVerificationPrompt as BaseEmailVerificationPrompt;

class EmailVerificationPrompt extends BaseEmailVerificationPrompt
{
    protected static string $view = 'filament.business.auth.verify-email';
}

