<?php

namespace App\Filament\Business\Pages\Auth;

use Filament\Pages\Auth\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;

class RequestPasswordReset extends BaseRequestPasswordReset
{
    protected static string $view = 'filament.business.auth.password-reset-request';
}

