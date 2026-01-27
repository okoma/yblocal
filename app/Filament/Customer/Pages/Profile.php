<?php

namespace App\Filament\Customer\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class Profile extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static string $view = 'filament.customer.pages.profile';
    
    protected static ?int $navigationSort = 10;
    
    public ?array $profileData = [];
    public ?array $passwordData = [];

    public function mount(): void
    {
        $this->fillForms();
    }

    protected function fillForms(): void
    {
        $user = Auth::user();
        
        $this->profileData = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'bio' => $user->bio,
            'avatar' => $user->avatar,
        ];
    }

    protected function getForms(): array
    {
        return [
            'profileForm',
            'passwordForm',
        ];
    }

    public function profileForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Profile Information')
                    ->description('Update your account profile information')
                    ->schema([
                        Forms\Components\FileUpload::make('avatar')
                            ->label('Profile Photo')
                            ->image()
                            ->avatar()
                            ->directory('avatars')
                            ->maxSize(2048)
                            ->imageEditor()
                            ->columnSpanFull(),
                        
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(20)
                            ->placeholder('+234 800 123 4567'),
                        
                        Forms\Components\Textarea::make('bio')
                            ->maxLength(500)
                            ->rows(3)
                            ->placeholder('Tell us a bit about yourself...')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ])
            ->statePath('profileData')
            ->model(Auth::user());
    }

    public function passwordForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Change Password')
                    ->description('Update your password')
                    ->schema([
                        Forms\Components\TextInput::make('current_password')
                            ->label('Current Password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->currentPassword(),
                        
                        Forms\Components\TextInput::make('password')
                            ->label('New Password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->minLength(8)
                            ->confirmed(),
                        
                        Forms\Components\TextInput::make('password_confirmation')
                            ->label('Confirm New Password')
                            ->password()
                            ->revealable()
                            ->required(),
                    ])
                    ->columns(1),
            ])
            ->statePath('passwordData');
    }

    public function updateProfile(): void
    {
        $data = $this->profileForm->getState();
        
        $user = Auth::user();
        $user->update($data);
        
        Notification::make()
            ->success()
            ->title('Profile updated')
            ->body('Your profile has been updated successfully.')
            ->send();
        
        // Refresh the form
        $this->fillForms();
    }

    public function updatePassword(): void
    {
        $data = $this->passwordForm->getState();
        
        $user = Auth::user();
        $user->update([
            'password' => Hash::make($data['password']),
        ]);
        
        // Reset password form
        $this->passwordData = [];
        
        Notification::make()
            ->success()
            ->title('Password updated')
            ->body('Your password has been changed successfully.')
            ->send();
    }
    
    public function deleteAccount(): void
    {
        // This would require additional confirmation and handling
        Notification::make()
            ->warning()
            ->title('Account Deletion')
            ->body('Please contact support to delete your account.')
            ->send();
    }
}
