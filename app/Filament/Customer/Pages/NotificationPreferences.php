<?php

namespace App\Filament\Customer\Pages;

use App\Models\UserPreference;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class NotificationPreferences extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-bell';

    protected static string $view = 'filament.customer.pages.notification-preferences';
    
    protected static ?string $navigationLabel = 'Notifications';
    
    protected static ?int $navigationSort = 9;
    
    protected static ?string $title = 'Notification Preferences';
    
    public ?array $data = [];

    public function mount(): void
    {
        $user = Auth::user();
        $preferences = UserPreference::getForUser($user->id);
        
        $this->form->fill([
            // Email preferences
            'notify_review_reply_received' => $preferences->notify_review_reply_received ?? true,
            'notify_inquiry_response_received' => $preferences->notify_inquiry_response_received ?? true,
            'notify_saved_business_updates' => $preferences->notify_saved_business_updates ?? true,
            'notify_promotions_customer' => $preferences->notify_promotions_customer ?? true,
            'notify_newsletter_customer' => $preferences->notify_newsletter_customer ?? true,
            
            // In-app preferences
            'notify_review_reply_app' => $preferences->notify_review_reply_app ?? true,
            'notify_inquiry_response_app' => $preferences->notify_inquiry_response_app ?? true,
            'notify_saved_business_updates_app' => $preferences->notify_saved_business_updates_app ?? true,
            'notify_promotions_app' => $preferences->notify_promotions_app ?? false,
            'notify_quote_responses' => $preferences->notify_quote_responses ?? true,
            'notify_quote_updates' => $preferences->notify_quote_updates ?? true,
            'notify_quote_responses_app' => $preferences->notify_quote_responses_app ?? true,
            'notify_quote_updates_app' => $preferences->notify_quote_updates_app ?? true,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Email Notifications')
                    ->description('Choose which emails you want to receive')
                    ->icon('heroicon-o-envelope')
                    ->schema([
                        Forms\Components\Toggle::make('notify_review_reply_received')
                            ->label('Review Replies')
                            ->helperText('Get notified when a business replies to your review')
                            ->inline(false)
                            ->onIcon('heroicon-o-bell')
                            ->offIcon('heroicon-o-bell-slash'),
                        
                        Forms\Components\Toggle::make('notify_inquiry_response_received')
                            ->label('Inquiry Responses')
                            ->helperText('Get notified when a business responds to your inquiry')
                            ->inline(false)
                            ->onIcon('heroicon-o-bell')
                            ->offIcon('heroicon-o-bell-slash'),
                        
                        Forms\Components\Toggle::make('notify_saved_business_updates')
                            ->label('Business Updates')
                            ->helperText('Get updates from businesses you\'ve saved')
                            ->inline(false)
                            ->onIcon('heroicon-o-bell')
                            ->offIcon('heroicon-o-bell-slash'),
                        
                        Forms\Components\Toggle::make('notify_promotions_customer')
                            ->label('Special Offers & Promotions')
                            ->helperText('Receive exclusive deals from businesses')
                            ->inline(false)
                            ->onIcon('heroicon-o-bell')
                            ->offIcon('heroicon-o-bell-slash'),
                        
                        Forms\Components\Toggle::make('notify_newsletter_customer')
                            ->label('Newsletter & Platform Updates')
                            ->helperText('Stay informed about new features and platform news')
                            ->inline(false)
                            ->onIcon('heroicon-o-bell')
                            ->offIcon('heroicon-o-bell-slash'),
                    ])
                    ->columns(1)
                    ->collapsible(),
                
                Forms\Components\Section::make('In-App Notifications')
                    ->description('Manage notifications you see within the platform')
                    ->icon('heroicon-o-bell-alert')
                    ->schema([
                        Forms\Components\Toggle::make('notify_review_reply_app')
                            ->label('Review Replies')
                            ->helperText('Show in-app notification for review replies')
                            ->inline(false)
                            ->onIcon('heroicon-o-bell')
                            ->offIcon('heroicon-o-bell-slash'),
                        
                        Forms\Components\Toggle::make('notify_inquiry_response_app')
                            ->label('Inquiry Responses')
                            ->helperText('Show in-app notification for inquiry responses')
                            ->inline(false)
                            ->onIcon('heroicon-o-bell')
                            ->offIcon('heroicon-o-bell-slash'),
                        
                        Forms\Components\Toggle::make('notify_saved_business_updates_app')
                            ->label('Business Updates')
                            ->helperText('Show updates from saved businesses')
                            ->inline(false)
                            ->onIcon('heroicon-o-bell')
                            ->offIcon('heroicon-o-bell-slash'),
                        
                        Forms\Components\Toggle::make('notify_promotions_app')
                            ->label('Promotions')
                            ->helperText('Show promotional notifications (disabled by default)')
                            ->inline(false)
                            ->onIcon('heroicon-o-bell')
                            ->offIcon('heroicon-o-bell-slash'),
                        
                        Forms\Components\Toggle::make('notify_quote_responses_app')
                            ->label('Quote Responses')
                            ->helperText('Show in-app notification when businesses submit quotes')
                            ->inline(false)
                            ->onIcon('heroicon-o-bell')
                            ->offIcon('heroicon-o-bell-slash'),
                        
                        Forms\Components\Toggle::make('notify_quote_updates_app')
                            ->label('Quote Updates')
                            ->helperText('Show in-app notification for quote status changes')
                            ->inline(false)
                            ->onIcon('heroicon-o-bell')
                            ->offIcon('heroicon-o-bell-slash'),
                    ])
                    ->columns(1)
                    ->collapsible(),
                
                Forms\Components\Section::make('Quick Actions')
                    ->schema([
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('enable_all_email')
                                ->label('Enable All Email Notifications')
                                ->icon('heroicon-o-check-circle')
                                ->color('success')
                                ->action(function () {
                                    $this->form->fill([
                                        'notify_review_reply_received' => true,
                                        'notify_inquiry_response_received' => true,
                                        'notify_saved_business_updates' => true,
                                        'notify_promotions_customer' => true,
                                        'notify_newsletter_customer' => true,
                                        'notify_quote_responses' => true,
                                        'notify_quote_updates' => true,
                                    ]);
                                }),
                            
                            Forms\Components\Actions\Action::make('disable_all_email')
                                ->label('Disable All Email Notifications')
                                ->icon('heroicon-o-x-circle')
                                ->color('danger')
                                ->requiresConfirmation()
                                ->modalHeading('Disable All Email Notifications?')
                                ->modalDescription('You will stop receiving all email notifications. You can enable them again at any time.')
                                ->action(function () {
                                    $this->form->fill([
                                        'notify_review_reply_received' => false,
                                        'notify_inquiry_response_received' => false,
                                        'notify_saved_business_updates' => false,
                                        'notify_promotions_customer' => false,
                                        'notify_newsletter_customer' => false,
                                    ]);
                                }),
                        ])
                    ])
                    ->collapsible()
                    ->collapsed(),
                
                Forms\Components\Section::make('💡 Notification Types Explained')
                    ->description('Learn more about each notification type')
                    ->schema([
                        Forms\Components\Placeholder::make('review_replies_info')
                            ->label('Review Replies')
                            ->content('When a business owner responds to your review'),
                        
                        Forms\Components\Placeholder::make('inquiry_responses_info')
                            ->label('Inquiry Responses')
                            ->content('When a business replies to your contact inquiry'),
                        
                        Forms\Components\Placeholder::make('business_updates_info')
                            ->label('Business Updates')
                            ->content('News and announcements from businesses you\'ve saved'),
                        
                        Forms\Components\Placeholder::make('promotions_info')
                            ->label('Promotions')
                            ->content('Special offers, deals, and exclusive discounts'),
                        
                        Forms\Components\Placeholder::make('newsletter_info')
                            ->label('Newsletter')
                            ->content('Platform updates, tips, and featured businesses'),
                        
                        Forms\Components\Placeholder::make('quote_responses_info')
                            ->label('Quote Responses')
                            ->content('When businesses submit quotes for your quote requests'),
                        
                        Forms\Components\Placeholder::make('quote_updates_info')
                            ->label('Quote Updates')
                            ->content('When your quotes are shortlisted, accepted, or rejected by customers'),
                    ])
                    ->columns(1)
                    ->collapsible()
                    ->collapsed(),
            ])
            ->statePath('data');
    }

    public function updatePreferences(): void
    {
        $data = $this->form->getState();
        
        $user = Auth::user();
        
        // Update or create preferences
        UserPreference::updateOrCreate(
            ['user_id' => $user->id],
            $data
        );
        
        Notification::make()
            ->success()
            ->title('Preferences updated')
            ->body('Your notification preferences have been saved successfully.')
            ->send();
    }
}