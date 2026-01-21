<?php

namespace App\Filament\Admin\Pages;

use Filament\Forms;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class GeneralSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    
    protected static ?string $navigationLabel = 'General Settings';
    
    protected static ?string $navigationGroup = 'Settings';
    
    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.admin.pages.general-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->loadSettings();
    }

    protected function loadSettings(): void
    {
        // Load from cache or database (using cache for now, can be moved to settings table)
        $this->data = [
            'telegram' => [
                'is_enabled' => Cache::get('telegram_notifications_enabled', false),
                'bot_token' => Cache::get('telegram_bot_token', ''),
                'webhook_url' => Cache::get('telegram_webhook_url', ''),
                'api_url' => Cache::get('telegram_api_url', 'https://api.telegram.org/bot'),
            ],
            'whatsapp' => [
                'is_enabled' => Cache::get('whatsapp_notifications_enabled', false),
                'api_key' => Cache::get('whatsapp_api_key', ''),
                'api_url' => Cache::get('whatsapp_api_url', ''),
                'webhook_url' => Cache::get('whatsapp_webhook_url', ''),
                'sender_id' => Cache::get('whatsapp_sender_id', ''),
            ],
        ];

        $this->form->fill($this->data);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('GeneralSettings')
                    ->tabs([
                        Tabs\Tab::make('Telegram Notifications')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->schema([
                                Forms\Components\Section::make('Telegram Bot Configuration')
                                    ->description('Configure Telegram bot for sending notifications to users.')
                                    ->schema([
                                        Forms\Components\Toggle::make('telegram.is_enabled')
                                            ->label('Enable Telegram Notifications')
                                            ->helperText('Allow users to receive notifications via Telegram')
                                            ->live(),
                                        
                                        Forms\Components\TextInput::make('telegram.bot_token')
                                            ->label('Bot Token')
                                            ->password()
                                            ->maxLength(255)
                                            ->helperText('Telegram bot token from @BotFather')
                                            ->visible(fn (Forms\Get $get) => $get('telegram.is_enabled')),
                                        
                                        Forms\Components\TextInput::make('telegram.webhook_url')
                                            ->label('Webhook URL')
                                            ->url()
                                            ->maxLength(500)
                                            ->helperText('Webhook URL for receiving updates from Telegram')
                                            ->visible(fn (Forms\Get $get) => $get('telegram.is_enabled')),
                                        
                                        Forms\Components\TextInput::make('telegram.api_url')
                                            ->label('API URL')
                                            ->url()
                                            ->maxLength(500)
                                            ->default('https://api.telegram.org/bot')
                                            ->helperText('Telegram Bot API base URL')
                                            ->visible(fn (Forms\Get $get) => $get('telegram.is_enabled')),
                                    ])
                                    ->columns(2),
                            ]),
                        
                        Tabs\Tab::make('WhatsApp Notifications')
                            ->icon('heroicon-o-phone')
                            ->schema([
                                Forms\Components\Section::make('WhatsApp API Configuration')
                                    ->description('Configure WhatsApp API for sending lead notifications (leads only).')
                                    ->schema([
                                        Forms\Components\Toggle::make('whatsapp.is_enabled')
                                            ->label('Enable WhatsApp Notifications')
                                            ->helperText('Allow users to receive lead notifications via WhatsApp')
                                            ->live(),
                                        
                                        Forms\Components\TextInput::make('whatsapp.api_key')
                                            ->label('API Key')
                                            ->password()
                                            ->maxLength(255)
                                            ->helperText('WhatsApp API key')
                                            ->visible(fn (Forms\Get $get) => $get('whatsapp.is_enabled')),
                                        
                                        Forms\Components\TextInput::make('whatsapp.api_url')
                                            ->label('API URL')
                                            ->url()
                                            ->maxLength(500)
                                            ->helperText('WhatsApp API endpoint URL')
                                            ->visible(fn (Forms\Get $get) => $get('whatsapp.is_enabled')),
                                        
                                        Forms\Components\TextInput::make('whatsapp.webhook_url')
                                            ->label('Webhook URL')
                                            ->url()
                                            ->maxLength(500)
                                            ->helperText('Webhook URL for receiving WhatsApp updates')
                                            ->visible(fn (Forms\Get $get) => $get('whatsapp.is_enabled')),
                                        
                                        Forms\Components\TextInput::make('whatsapp.sender_id')
                                            ->label('Sender ID')
                                            ->maxLength(255)
                                            ->helperText('WhatsApp sender ID or phone number')
                                            ->visible(fn (Forms\Get $get) => $get('whatsapp.is_enabled')),
                                    ])
                                    ->columns(2),
                                
                                Forms\Components\Placeholder::make('whatsapp_info')
                                    ->label('')
                                    ->content('Note: WhatsApp notifications are only sent for new leads, not for reviews, campaigns, or other notifications.')
                                    ->visible(fn (Forms\Get $get) => $get('whatsapp.is_enabled')),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Save Telegram settings
        Cache::forever('telegram_notifications_enabled', $data['telegram']['is_enabled'] ?? false);
        Cache::forever('telegram_bot_token', $data['telegram']['bot_token'] ?? '');
        Cache::forever('telegram_webhook_url', $data['telegram']['webhook_url'] ?? '');
        Cache::forever('telegram_api_url', $data['telegram']['api_url'] ?? 'https://api.telegram.org/bot');
        
        // Save WhatsApp settings
        Cache::forever('whatsapp_notifications_enabled', $data['whatsapp']['is_enabled'] ?? false);
        Cache::forever('whatsapp_api_key', $data['whatsapp']['api_key'] ?? '');
        Cache::forever('whatsapp_api_url', $data['whatsapp']['api_url'] ?? '');
        Cache::forever('whatsapp_webhook_url', $data['whatsapp']['webhook_url'] ?? '');
        Cache::forever('whatsapp_sender_id', $data['whatsapp']['sender_id'] ?? '');

        Notification::make()
            ->success()
            ->title('Settings saved')
            ->body('General settings have been updated successfully.')
            ->send();
    }
}
