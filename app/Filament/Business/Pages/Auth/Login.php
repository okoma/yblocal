<?php

namespace App\Filament\Business\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;

class Login extends BaseLogin
{
    protected static string $view = 'filament.business.auth.login';
    
    // Optional: customize if needed
    // protected function getForms(): array
    // {
    //     return [
    //         'form' => $this->form(
    //             $this->makeForm()
    //                 ->schema([
    //                     $this->getEmailFormComponent(),
    //                     $this->getPasswordFormComponent(),
    //                     $this->getRememberFormComponent(),
    //                 ])
    //                 ->statePath('data'),
    //         ),
    //     ];
    // }
}