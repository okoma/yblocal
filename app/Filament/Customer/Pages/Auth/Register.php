<?php

namespace App\Filament\Customer\Pages\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class Register extends BaseRegister
{
    protected static string $view = 'filament.customer.auth.register';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Full name')
                    ->required()
                    ->maxLength(255)
                    ->autofocus(),

                $this->getEmailFormComponent(),

                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->required()
                    ->rule(Password::min(8)->mixedCase()->numbers()->symbols())
                    ->helperText('Use at least 8 characters with upper & lower case letters, numbers, and a symbol.')
                    ->revealable()
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->same('passwordConfirmation')
                    ->validationAttribute('password'),

                TextInput::make('passwordConfirmation')
                    ->label('Confirm password')
                    ->password()
                    ->required()
                    ->revealable()
                    ->dehydrated(false),
            ]);
    }

    protected function handleRegistration(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'], // Already hashed in form
            'role' => UserRole::CUSTOMER,
        ]);
    }
}