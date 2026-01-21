<?php
// ============================================
// app/Filament/Business/Pages/AccountPreferences.php
// Account Preferences - DATABASE VERSION (Recommended)
// Stores preferences in user_preferences table
// ============================================

namespace App\Filament\Business\Pages;

use App\Models\UserPreference;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class AccountPreferences extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Preferences';

    protected static ?string $navigationGroup = 'Account';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.business.pages.account-preferences';

    public ?array $data = [];

    public function mount(): void
    {
        $this->fillForm();
    }

    protected function fillForm(): void
    {
        $user = auth()->user();
        
        // Get or create user preferences
        $preferences = UserPreference::getForUser($user->id);

        $this->data = [
            // Notification Preferences
            'email_notifications' => $preferences->email_notifications,
            'telegram_notifications' => $preferences->telegram_notifications,
            'telegram_username' => $preferences->telegram_username,
            'telegram_chat_id' => $preferences->telegram_chat_id,
            'notify_new_leads_telegram' => $preferences->notify_new_leads_telegram,
            'notify_new_reviews_telegram' => $preferences->notify_new_reviews_telegram,
            'notify_review_replies_telegram' => $preferences->notify_review_replies_telegram,
            'notify_verifications_telegram' => $preferences->notify_verifications_telegram,
            'notify_premium_expiring_telegram' => $preferences->notify_premium_expiring_telegram,
            'notify_campaign_updates_telegram' => $preferences->notify_campaign_updates_telegram,
            'whatsapp_notifications' => $preferences->whatsapp_notifications,
            'whatsapp_number' => $preferences->whatsapp_number,
            'notify_new_leads_whatsapp' => $preferences->notify_new_leads_whatsapp,
            'notify_new_reviews_whatsapp' => $preferences->notify_new_reviews_whatsapp,
            'whatsapp_verified' => $preferences->whatsapp_verified,
            'notify_new_leads' => $preferences->notify_new_leads,
            'notify_new_reviews' => $preferences->notify_new_reviews,
            'notify_review_replies' => $preferences->notify_review_replies,
            'notify_verifications' => $preferences->notify_verifications,
            'notify_premium_expiring' => $preferences->notify_premium_expiring,
            'notify_campaign_updates' => $preferences->notify_campaign_updates,
            
            // Display Preferences
            'theme' => $preferences->theme,
            'language' => $preferences->language,
            'timezone' => $preferences->timezone,
            'date_format' => $preferences->date_format,
            'time_format' => $preferences->time_format,
            
            // Privacy Preferences
            'profile_visibility' => $preferences->profile_visibility,
            'show_email' => $preferences->show_email,
            'show_phone' => $preferences->show_phone,
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Preferences')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Notifications')
                            ->icon('heroicon-o-bell')
                            ->schema([
                                Forms\Components\Section::make('Email Notifications')
                                    ->description('Manage your email notification preferences.')
                                    ->schema([
                                        Forms\Components\Toggle::make('email_notifications')
                                            ->label('Enable Email Notifications')
                                            ->helperText('Receive notifications via email')
                                            ->live(),

                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Toggle::make('notify_new_leads')
                                                    ->label('New Leads')
                                                    ->helperText('When someone submits a lead'),

                                                Forms\Components\Toggle::make('notify_new_reviews')
                                                    ->label('New Reviews')
                                                    ->helperText('When you receive a new review'),

                                                Forms\Components\Toggle::make('notify_review_replies')
                                                    ->label('Review Replies')
                                                    ->helperText('When customers reply to your responses'),

                                                Forms\Components\Toggle::make('notify_verifications')
                                                    ->label('Verification Updates')
                                                    ->helperText('Status updates on verification requests'),

                                                Forms\Components\Toggle::make('notify_premium_expiring')
                                                    ->label('Premium Expiring')
                                                    ->helperText('When your premium subscription is expiring'),

                                                Forms\Components\Toggle::make('notify_campaign_updates')
                                                    ->label('Campaign Updates')
                                                    ->helperText('Updates about your ad campaigns'),
                                            ])
                                            ->visible(fn (Forms\Get $get) => $get('email_notifications')),
                                    ]),
                                
                                Forms\Components\Section::make('Telegram Notifications')
                                    ->description('Receive notifications via Telegram.')
                                    ->schema([
                                        Forms\Components\Toggle::make('telegram_notifications')
                                            ->label('Enable Telegram Notifications')
                                            ->helperText('Receive notifications via Telegram')
                                            ->default(true)
                                            ->live(),
                                        
                                        Forms\Components\TextInput::make('telegram_username')
                                            ->label('Telegram Username')
                                            ->placeholder('@username')
                                            ->maxLength(255)
                                            ->helperText('Your Telegram username (e.g., @username)')
                                            ->visible(fn (Forms\Get $get) => $get('telegram_notifications')),
                                        
                                        Forms\Components\TextInput::make('telegram_chat_id')
                                            ->label('Telegram Chat ID')
                                            ->maxLength(255)
                                            ->helperText('Your Telegram Chat ID (if available, this will be used instead of username)')
                                            ->visible(fn (Forms\Get $get) => $get('telegram_notifications')),
                                        
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Toggle::make('notify_new_leads_telegram')
                                                    ->label('New Leads')
                                                    ->helperText('When someone submits a lead')
                                                    ->default(false),
                                                
                                                Forms\Components\Toggle::make('notify_new_reviews_telegram')
                                                    ->label('New Reviews')
                                                    ->helperText('When you receive a new review')
                                                    ->default(false),
                                                
                                                Forms\Components\Toggle::make('notify_review_replies_telegram')
                                                    ->label('Review Replies')
                                                    ->helperText('When customers reply to your responses')
                                                    ->default(false),
                                                
                                                Forms\Components\Toggle::make('notify_verifications_telegram')
                                                    ->label('Verification Updates')
                                                    ->helperText('Status updates on verification requests')
                                                    ->default(false),
                                                
                                                Forms\Components\Toggle::make('notify_premium_expiring_telegram')
                                                    ->label('Premium Expiring')
                                                    ->helperText('When your premium subscription is expiring')
                                                    ->default(false),
                                                
                                                Forms\Components\Toggle::make('notify_campaign_updates_telegram')
                                                    ->label('Campaign Updates')
                                                    ->helperText('Updates about your ad campaigns')
                                                    ->default(false),
                                            ])
                                            ->visible(fn (Forms\Get $get) => $get('telegram_notifications')),
                                    ]),
                                
                                Forms\Components\Section::make('WhatsApp Notifications')
                                    ->description('Receive notifications via WhatsApp (leads and reviews only).')
                                    ->schema([
                                        Forms\Components\Toggle::make('whatsapp_notifications')
                                            ->label('Enable WhatsApp Notifications')
                                            ->helperText('Receive notifications via WhatsApp')
                                            ->default(true)
                                            ->live(),
                                        
                                        Forms\Components\TextInput::make('whatsapp_number')
                                            ->label('WhatsApp Number')
                                            ->tel()
                                            ->placeholder('+2348012345678')
                                            ->maxLength(20)
                                            ->helperText('Your WhatsApp number with country code (e.g., +2348012345678)')
                                            ->required(fn (Forms\Get $get) => $get('whatsapp_notifications'))
                                            ->visible(fn (Forms\Get $get) => $get('whatsapp_notifications'))
                                            ->live(),
                                        
                                        Forms\Components\Placeholder::make('whatsapp_verification_status')
                                            ->label('')
                                            ->content(function (Forms\Get $get) {
                                                $verified = $get('whatsapp_verified');
                                                if ($verified) {
                                                    return new \Illuminate\Support\HtmlString(
                                                        '<div class="flex items-center gap-2 text-success-600 dark:text-success-400">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                            </svg>
                                                            <span>WhatsApp number verified</span>
                                                        </div>'
                                                    );
                                                }
                                                return new \Illuminate\Support\HtmlString(
                                                    '<div class="flex items-center gap-2 text-warning-600 dark:text-warning-400">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                                        </svg>
                                                        <span>WhatsApp number not verified. Please verify to receive notifications.</span>
                                                    </div>'
                                                );
                                            })
                                            ->visible(fn (Forms\Get $get) => $get('whatsapp_notifications') && !empty($get('whatsapp_number'))),
                                        
                                        Forms\Components\TextInput::make('whatsapp_verification_code')
                                            ->label('Verification Code')
                                            ->placeholder('Enter 6-digit code')
                                            ->maxLength(6)
                                            ->numeric()
                                            ->helperText('Enter the verification code sent to your WhatsApp number')
                                            ->visible(fn (Forms\Get $get) => $get('whatsapp_notifications') && !empty($get('whatsapp_number')) && !$get('whatsapp_verified'))
                                            ->suffixActions([
                                                Forms\Components\Actions\Action::make('sendVerificationCode')
                                                    ->label('Send Code')
                                                    ->icon('heroicon-o-paper-airplane')
                                                    ->action('sendWhatsAppVerificationCode'),
                                                Forms\Components\Actions\Action::make('verifyCode')
                                                    ->label('Verify')
                                                    ->icon('heroicon-o-check')
                                                    ->action('verifyWhatsAppCode')
                                                    ->color('success'),
                                            ]),
                                        
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Toggle::make('notify_new_leads_whatsapp')
                                                    ->label('New Leads')
                                                    ->helperText('When someone submits a lead')
                                                    ->default(false)
                                                    ->disabled(fn (Forms\Get $get) => !$get('whatsapp_verified')),
                                                
                                                Forms\Components\Toggle::make('notify_new_reviews_whatsapp')
                                                    ->label('New Reviews')
                                                    ->helperText('When you receive a new review')
                                                    ->default(false)
                                                    ->disabled(fn (Forms\Get $get) => !$get('whatsapp_verified')),
                                            ])
                                            ->visible(fn (Forms\Get $get) => $get('whatsapp_notifications')),
                                        
                                        Forms\Components\Placeholder::make('whatsapp_info')
                                            ->label('')
                                            ->content('Note: WhatsApp notifications are only sent for new leads and reviews.')
                                            ->visible(fn (Forms\Get $get) => $get('whatsapp_notifications')),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Display')
                            ->icon('heroicon-o-computer-desktop')
                            ->schema([
                                Forms\Components\Section::make('Display Settings')
                                    ->description('Customize how the dashboard looks and feels.')
                                    ->schema([
                                        Forms\Components\Select::make('theme')
                                            ->options([
                                                'light' => 'Light',
                                                'dark' => 'Dark',
                                                'system' => 'System Default',
                                            ])
                                            ->default('system')
                                            ->helperText('Choose your preferred theme'),

                                        Forms\Components\Select::make('language')
                                            ->options([
                                                'en' => 'English',
                                                'fr' => 'French',
                                                'es' => 'Spanish',
                                            ])
                                            ->default('en')
                                            ->helperText('Select your language'),

                                        Forms\Components\Select::make('timezone')
                                            ->options([
                                                'Africa/Lagos' => 'Lagos (GMT+1)',
                                                'UTC' => 'UTC',
                                                'America/New_York' => 'New York (EST)',
                                                'Europe/London' => 'London (GMT)',
                                            ])
                                            ->searchable()
                                            ->default('Africa/Lagos')
                                            ->helperText('Your local timezone'),

                                        Forms\Components\Select::make('date_format')
                                            ->options([
                                                'M j, Y' => now()->format('M j, Y'),
                                                'd/m/Y' => now()->format('d/m/Y'),
                                                'Y-m-d' => now()->format('Y-m-d'),
                                                'F j, Y' => now()->format('F j, Y'),
                                            ])
                                            ->default('M j, Y')
                                            ->helperText('How dates are displayed'),

                                        Forms\Components\Select::make('time_format')
                                            ->options([
                                                '12h' => '12-hour (e.g., 2:30 PM)',
                                                '24h' => '24-hour (e.g., 14:30)',
                                            ])
                                            ->default('12h')
                                            ->helperText('Time display format'),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('Privacy')
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                Forms\Components\Section::make('Privacy Settings')
                                    ->description('Control who can see your information.')
                                    ->schema([
                                        Forms\Components\Select::make('profile_visibility')
                                            ->options([
                                                'public' => 'Public - Anyone can see',
                                                'registered' => 'Registered Users Only',
                                                'private' => 'Private - Only You',
                                            ])
                                            ->default('public')
                                            ->helperText('Who can view your profile'),

                                        Forms\Components\Toggle::make('show_email')
                                            ->label('Show Email on Profile')
                                            ->helperText('Display your email publicly'),

                                        Forms\Components\Toggle::make('show_phone')
                                            ->label('Show Phone on Profile')
                                            ->helperText('Display your phone number publicly'),
                                    ])
                                    ->columns(1),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function sendWhatsAppVerificationCode(): void
    {
        $data = $this->form->getState();
        $whatsappNumber = $data['whatsapp_number'] ?? null;
        
        if (empty($whatsappNumber)) {
            Notification::make()
                ->danger()
                ->title('WhatsApp Number Required')
                ->body('Please enter your WhatsApp number first.')
                ->send();
            return;
        }
        
        // Generate 6-digit verification code
        $verificationCode = str_pad((string) rand(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // TODO: Send verification code via WhatsApp API
        // For now, just store it
        $user = auth()->user();
        $preferences = UserPreference::getForUser($user->id);
        $preferences->update([
            'whatsapp_verification_code' => $verificationCode,
        ]);
        
        // Update form data
        $this->data['whatsapp_verification_code'] = $verificationCode;
        $this->form->fill($this->data);
        
        Notification::make()
            ->success()
            ->title('Verification Code Sent')
            ->body('A verification code has been sent to ' . $whatsappNumber . '. Please check your WhatsApp messages.')
            ->send();
    }
    
    public function verifyWhatsAppCode(): void
    {
        $data = $this->form->getState();
        $enteredCode = $data['whatsapp_verification_code'] ?? null;
        
        if (empty($enteredCode)) {
            Notification::make()
                ->danger()
                ->title('Code Required')
                ->body('Please enter the verification code.')
                ->send();
            return;
        }
        
        $user = auth()->user();
        $preferences = UserPreference::getForUser($user->id);
        
        if ($preferences->whatsapp_verification_code === $enteredCode) {
            $preferences->update([
                'whatsapp_verified' => true,
                'whatsapp_verified_at' => now(),
                'whatsapp_verification_code' => null,
            ]);
            
            Notification::make()
                ->success()
                ->title('WhatsApp Verified')
                ->body('Your WhatsApp number has been verified successfully!')
                ->send();
            
            // Clear verification code from form
            $this->data['whatsapp_verification_code'] = null;
            $this->data['whatsapp_verified'] = true;
            $this->form->fill($this->data);
        } else {
            Notification::make()
                ->danger()
                ->title('Invalid Code')
                ->body('The verification code you entered is incorrect. Please try again.')
                ->send();
        }
    }
    
    public function save(): void
    {
        $data = $this->form->getState();

        $user = auth()->user();
        
        // Remove verification code from data (don't save it, it's temporary)
        unset($data['whatsapp_verification_code']);
        
        // Update or create preferences
        UserPreference::updateOrCreate(
            ['user_id' => $user->id],
            $data
        );

        Notification::make()
            ->success()
            ->title('Preferences Saved')
            ->body('Your preferences have been updated successfully.')
            ->send();

        // Refresh the form
        $this->fillForm();
    }
}