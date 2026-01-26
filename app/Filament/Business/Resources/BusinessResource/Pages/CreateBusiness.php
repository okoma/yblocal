<?php
// ============================================
// app/Filament/Business/Resources/BusinessResource/Pages/CreateBusiness.php
// FULLY CORRECTED VERSION - All Syntax Errors Fixed
// ============================================

namespace App\Filament\Business\Resources\BusinessResource\Pages;

use App\Filament\Business\Resources\BusinessResource;
use App\Services\ActiveBusiness;
use App\Services\NewBusinessPlanLimits;
use App\Models\BusinessType;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Amenity;
use App\Models\Location;
use App\Models\FAQ;
use App\Models\SocialAccount;
use App\Models\Official;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Filament\Forms;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Illuminate\Support\Facades\Auth;

class CreateBusiness extends CreateRecord
{
    use HasWizard;
    
    protected static string $resource = BusinessResource::class;
    
    protected function getSteps(): array
    {
        return [
            // Step 1: Basic Information
            Wizard\Step::make('Basic Information')
                ->description('Enter your business details, amenities, and legal information')
                ->schema([
                    Forms\Components\Section::make('Business Details')
                        ->schema([
                            Forms\Components\TextInput::make('business_name')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('e.g., Okoma Technologies Ltd')
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn ($state, Forms\Set $set) => 
                                    $set('slug', \Illuminate\Support\Str::slug($state))
                                ),
                            
                            Forms\Components\TextInput::make('slug')
                                ->required()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true)
                                ->disabled()
                                ->dehydrated()
                                ->placeholder('auto-generated-from-business-name')
                                ->helperText('URL-friendly version of your business name (auto-generated)'),
                            
                            Forms\Components\Select::make('business_type_id')
                                ->label('Business Type')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->relationship('businessType', 'name')
                                ->live()
                                ->afterStateUpdated(fn (Forms\Set $set) => $set('categories', [])),
                            
                            Forms\Components\Select::make('categories')
                                ->label('Categories')
                                ->multiple()
                                ->options(function (Forms\Get $get) {
                                    $businessTypeId = $get('business_type_id');
                                    if (!$businessTypeId) return [];
                                    
                                    return Category::where('business_type_id', $businessTypeId)
                                        ->where('is_active', true)
                                        ->pluck('name', 'id');
                                })
                                ->searchable()
                                ->preload()
                                ->disabled(fn (Forms\Get $get): bool => !$get('business_type_id'))
                                ->helperText('Select one or more categories for your business (select business type first)'),
                            
                            Forms\Components\Textarea::make('description')
                                ->required()
                                ->rows(4)
                                ->maxLength(1000)
                                ->placeholder('Tell customers about your business, services, and what makes you unique...')
                                ->helperText('Describe your business in detail')
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                    
                    Forms\Components\Section::make('Amenities & Payment')
                        ->description('What facilities and payment methods do you offer?')
                        ->schema([
                            Forms\Components\Select::make('payment_methods')
                                ->label('Payment Methods Accepted')
                                ->multiple()
                                ->options(PaymentMethod::where('is_active', true)->pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->helperText('Select all payment methods you accept'),
                            
                            Forms\Components\Select::make('amenities')
                                ->label('Amenities & Features')
                                ->multiple()
                                ->options(Amenity::where('is_active', true)->pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->helperText('Select all amenities available at your business'),
                        ])
                        ->columns(2)
                        ->collapsible(),
                    
                    Forms\Components\Section::make('Legal Information')
                        ->description('Business registration details (optional)')
                        ->schema([
                            Forms\Components\TextInput::make('registration_number')
                                ->label('CAC/RC Number')
                                ->maxLength(50)
                                ->placeholder('e.g., RC123456')
                                ->helperText('Business registration number'),
                            
                            Forms\Components\Select::make('entity_type')
                                ->options([
                                    'Sole Proprietorship' => 'Sole Proprietorship',
                                    'Partnership' => 'Partnership',
                                    'Limited Liability Company (LLC)' => 'Limited Liability Company (LLC)',
                                    'Corporation' => 'Corporation',
                                    'Non-Profit' => 'Non-Profit',
                                ]),
                            
                            Forms\Components\TextInput::make('years_in_business')
                                ->required()
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(100)
                                ->default(0)
                                ->helperText('How many years have you been operating?'),
                        ])
                        ->columns(3)
                        ->collapsible(),
                ])
                ->columns(1),
            
            // Step 2: Location & Contact
            Wizard\Step::make('Location & Contact')
                ->description('Where is your business located and how can customers reach you?')
                ->schema([
                    Forms\Components\Section::make('Business Location')
                        ->description('Provide your physical business address')
                        ->schema([
                            // ✅ FIXED: Changed state_id to state_location_id
                            Forms\Components\Select::make('state_location_id')
                                ->label('State')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->placeholder('Select your state')
                                ->options(function () {
                                    return Location::where('type', 'state')
                                        ->where('is_active', true)
                                        ->orderBy('name')
                                        ->pluck('name', 'id');
                                })
                                ->live()
                                ->afterStateUpdated(function (Forms\Set $set, $state) {
                                    $set('city_location_id', null);
                                    // Auto-fill state name
                                    $stateName = Location::find($state)?->name;
                                    $set('state', $stateName);
                                }),
                            
                            // ✅ FIXED: Changed city_id to city_location_id
                            Forms\Components\Select::make('city_location_id')
                                ->label('City')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->placeholder('Select your city')
                                ->options(function (Forms\Get $get) {
                                    $stateId = $get('state_location_id');
                                    if (!$stateId) return [];
                                    
                                    return Location::where('type', 'city')
                                        ->where('parent_id', $stateId)
                                        ->where('is_active', true)
                                        ->orderBy('name')
                                        ->pluck('name', 'id');
                                })
                                ->disabled(fn (Forms\Get $get): bool => !$get('state_location_id'))
                                ->helperText('Select state first')
                                ->live()
                                ->afterStateUpdated(function (Forms\Set $set, $state) {
                                    // Auto-fill city name
                                    $cityName = Location::find($state)?->name;
                                    $set('city', $cityName);
                                }),
                            
                            // ✅ ADDED: Hidden fields for state and city names (auto-filled)
                            Forms\Components\Hidden::make('state'),
                            Forms\Components\Hidden::make('city'),
                            
                            Forms\Components\TextInput::make('address')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Start typing an address...')
                                ->columnSpanFull()
                                ->helperText('Start typing to see address suggestions from Google Maps')
                                ->id('address-autocomplete')
                                ->extraAttributes(['data-google-autocomplete' => 'true']),
                            
                            Forms\Components\TextInput::make('area')
                                ->label('Area/Neighborhood')
                                ->maxLength(100)
                                ->placeholder('e.g., Ikeja, Victoria Island')
                                ->helperText('Optional: Specific area or neighborhood'),
                            
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('latitude')
                                        ->label('Latitude (GPS)')
                                        ->numeric()
                                        ->step(0.0000001)
                                        ->minValue(-90)
                                        ->maxValue(90)
                                        ->placeholder('e.g., 6.5244')
                                        ->helperText('Auto-filled from address or enter manually')
                                        ->id('latitude-field'),
                                    
                                    Forms\Components\TextInput::make('longitude')
                                        ->label('Longitude (GPS)')
                                        ->numeric()
                                        ->step(0.0000001)
                                        ->minValue(-180)
                                        ->maxValue(180)
                                        ->placeholder('e.g., 3.3792')
                                        ->helperText('Auto-filled from address or enter manually')
                                        ->id('longitude-field'),
                                ]),
                        ])
                        ->columns(2),
                    
                    Forms\Components\Section::make('Contact Information')
                        ->description('How can customers reach you?')
                        ->schema([
                            Forms\Components\TextInput::make('phone')
                                ->label('Phone Number')
                                ->tel()
                                ->maxLength(20)
                                ->placeholder('+234 800 123 4567'),
                            
                            Forms\Components\TextInput::make('email')
                                ->label('Email Address')
                                ->email()
                                ->maxLength(255)
                                ->placeholder('contact@yourbusiness.com'),
                            
                            Forms\Components\TextInput::make('whatsapp')
                                ->label('WhatsApp Number')
                                ->tel()
                                ->maxLength(20)
                                ->placeholder('+234 800 123 4567'),
                            
                            Forms\Components\TextInput::make('website')
                                ->label('Website URL')
                                ->url()
                                ->maxLength(255)
                                ->placeholder('https://www.yourbusiness.com'),
                            
                            Forms\Components\Textarea::make('whatsapp_message')
                                ->label('Default WhatsApp Message')
                                ->maxLength(500)
                                ->placeholder('Hello! I would like to inquire about your services...')
                                ->helperText('Pre-filled message when customers click WhatsApp')
                                ->rows(3)
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                ])
                ->columns(1),
            
// Step 3: Business Hours (Monday-Friday Required)
Wizard\Step::make('Business Hours')
->description('Set your operating hours (Monday-Friday required, weekend optional)')
->schema([
    Forms\Components\Section::make('Weekdays (Required)')
        ->description('Please specify your operating hours for Monday through Friday')
        ->schema([
            // Monday
            Forms\Components\Grid::make(4)
                ->schema([
                    Forms\Components\Placeholder::make('monday_label')
                        ->label('')
                        ->content('Monday'),
                    
                    Forms\Components\TimePicker::make('monday_open')
                        ->label('Opens')
                        ->required(fn (Forms\Get $get): bool => !$get('monday_closed'))
                        ->disabled(fn (Forms\Get $get) => $get('monday_closed')),
                    
                    Forms\Components\TimePicker::make('monday_close')
                        ->label('Closes')
                        ->required(fn (Forms\Get $get): bool => !$get('monday_closed'))
                        ->disabled(fn (Forms\Get $get) => $get('monday_closed')),
                    
                    Forms\Components\Toggle::make('monday_closed')
                        ->label('Closed')
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            if ($state) {
                                $set('monday_open', null);
                                $set('monday_close', null);
                            }
                        }),
                ]),
            
            // Tuesday
            Forms\Components\Grid::make(4)
                ->schema([
                    Forms\Components\Placeholder::make('tuesday_label')
                        ->label('')
                        ->content('Tuesday'),
                    
                    Forms\Components\TimePicker::make('tuesday_open')
                        ->label('Opens')
                        ->required(fn (Forms\Get $get): bool => !$get('tuesday_closed'))
                        ->disabled(fn (Forms\Get $get) => $get('tuesday_closed')),
                    
                    Forms\Components\TimePicker::make('tuesday_close')
                        ->label('Closes')
                        ->required(fn (Forms\Get $get): bool => !$get('tuesday_closed'))
                        ->disabled(fn (Forms\Get $get) => $get('tuesday_closed')),
                    
                    Forms\Components\Toggle::make('tuesday_closed')
                        ->label('Closed')
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            if ($state) {
                                $set('tuesday_open', null);
                                $set('tuesday_close', null);
                            }
                        }),
                ]),
            
            // Wednesday
            Forms\Components\Grid::make(4)
                ->schema([
                    Forms\Components\Placeholder::make('wednesday_label')
                        ->label('')
                        ->content('Wednesday'),
                    
                    Forms\Components\TimePicker::make('wednesday_open')
                        ->label('Opens')
                        ->required(fn (Forms\Get $get): bool => !$get('wednesday_closed'))
                        ->disabled(fn (Forms\Get $get) => $get('wednesday_closed')),
                    
                    Forms\Components\TimePicker::make('wednesday_close')
                        ->label('Closes')
                        ->required(fn (Forms\Get $get): bool => !$get('wednesday_closed'))
                        ->disabled(fn (Forms\Get $get) => $get('wednesday_closed')),
                    
                    Forms\Components\Toggle::make('wednesday_closed')
                        ->label('Closed')
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            if ($state) {
                                $set('wednesday_open', null);
                                $set('wednesday_close', null);
                            }
                        }),
                ]),
            
            // Thursday
            Forms\Components\Grid::make(4)
                ->schema([
                    Forms\Components\Placeholder::make('thursday_label')
                        ->label('')
                        ->content('Thursday'),
                    
                    Forms\Components\TimePicker::make('thursday_open')
                        ->label('Opens')
                        ->required(fn (Forms\Get $get): bool => !$get('thursday_closed'))
                        ->disabled(fn (Forms\Get $get) => $get('thursday_closed')),
                    
                    Forms\Components\TimePicker::make('thursday_close')
                        ->label('Closes')
                        ->required(fn (Forms\Get $get): bool => !$get('thursday_closed'))
                        ->disabled(fn (Forms\Get $get) => $get('thursday_closed')),
                    
                    Forms\Components\Toggle::make('thursday_closed')
                        ->label('Closed')
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            if ($state) {
                                $set('thursday_open', null);
                                $set('thursday_close', null);
                            }
                        }),
                ]),
            
            // Friday
            Forms\Components\Grid::make(4)
                ->schema([
                    Forms\Components\Placeholder::make('friday_label')
                        ->label('')
                        ->content('Friday'),
                    
                    Forms\Components\TimePicker::make('friday_open')
                        ->label('Opens')
                        ->required(fn (Forms\Get $get): bool => !$get('friday_closed'))
                        ->disabled(fn (Forms\Get $get) => $get('friday_closed')),
                    
                    Forms\Components\TimePicker::make('friday_close')
                        ->label('Closes')
                        ->required(fn (Forms\Get $get): bool => !$get('friday_closed'))
                        ->disabled(fn (Forms\Get $get) => $get('friday_closed')),
                    
                    Forms\Components\Toggle::make('friday_closed')
                        ->label('Closed')
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            if ($state) {
                                $set('friday_open', null);
                                $set('friday_close', null);
                            }
                        }),
                ]),
        ]),
    
        Forms\Components\Section::make('Weekend (Optional)')
        ->description('Optionally set your weekend hours')
        ->schema([
            // Saturday
            Forms\Components\Grid::make(4)
                ->schema([
                    Forms\Components\Placeholder::make('saturday_label')
                        ->label('')
                        ->content('Saturday'),
                    
                    Forms\Components\TimePicker::make('saturday_open')
                        ->label('Opens')
                        ->required(fn (Forms\Get $get): bool => !$get('saturday_closed'))
                        ->disabled(fn (Forms\Get $get) => $get('saturday_closed')),
                    
                    Forms\Components\TimePicker::make('saturday_close')
                        ->label('Closes')
                        ->required(fn (Forms\Get $get): bool => !$get('saturday_closed'))
                        ->disabled(fn (Forms\Get $get) => $get('saturday_closed')),
                    
                    Forms\Components\Toggle::make('saturday_closed')
                        ->label('Closed')
                        ->default(true)
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            if ($state) {
                                $set('saturday_open', null);
                                $set('saturday_close', null);
                            }
                        }),
                ]),
            
            // Sunday
            Forms\Components\Grid::make(4)
                ->schema([
                    Forms\Components\Placeholder::make('sunday_label')
                        ->label('')
                        ->content('Sunday'),
                    
                    Forms\Components\TimePicker::make('sunday_open')
                        ->label('Opens')
                        ->required(fn (Forms\Get $get): bool => !$get('sunday_closed'))
                        ->disabled(fn (Forms\Get $get) => $get('sunday_closed')),
                    
                    Forms\Components\TimePicker::make('sunday_close')
                        ->label('Closes')
                        ->required(fn (Forms\Get $get): bool => !$get('sunday_closed'))
                        ->disabled(fn (Forms\Get $get) => $get('sunday_closed')),
                    
                    Forms\Components\Toggle::make('sunday_closed')
                        ->label('Closed')
                        ->default(true)
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            if ($state) {
                                $set('sunday_open', null);
                                $set('sunday_close', null);
                            }
                        }),
                ]),
        ])
        ->collapsible()
        ->collapsed(),

])
->columns(1),
            
            // Step 4: FAQs (Optional)
            Wizard\Step::make('FAQs')
                ->description('Add frequently asked questions (optional - you can skip this step)')
                ->schema([
                    Forms\Components\Section::make('Frequently Asked Questions')
                        ->description(function () {
                            $maxFaqs = app(NewBusinessPlanLimits::class)->maxFaqs();
                            if ($maxFaqs === null) {
                                return 'Help customers by answering common questions about your business (Unlimited)';
                            }
                            return "Help customers by answering common questions about your business (Limit: {$maxFaqs} FAQs)";
                        })
                        ->schema([
                            Forms\Components\Repeater::make('faqs_temp')
                                ->label('')
                                ->schema([
                                    Forms\Components\TextInput::make('question')
                                        ->label('Question')
                                        ->required()
                                        ->maxLength(255)
                                        ->placeholder('e.g., What are your business hours?')
                                        ->columnSpanFull(),
                                    
                                    Forms\Components\Textarea::make('answer')
                                        ->label('Answer')
                                        ->required()
                                        ->rows(3)
                                        ->maxLength(1000)
                                        ->placeholder('Provide a clear and detailed answer to this question...')
                                        ->columnSpanFull(),
                                    
                                    Forms\Components\Toggle::make('is_active')
                                        ->label('Active')
                                        ->default(true)
                                        ->helperText('Show this FAQ on your business page'),
                                    
                                    Forms\Components\TextInput::make('order')
                                        ->label('Display Order')
                                        ->numeric()
                                        ->default(0)
                                        ->placeholder('0')
                                        ->helperText('Lower numbers appear first'),
                                ])
                                ->columns(2)
                                ->defaultItems(0)
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['question'] ?? 'New FAQ')
                                ->addActionLabel('Add FAQ')
                                ->maxItems(function () {
                                    $maxFaqs = app(NewBusinessPlanLimits::class)->maxFaqs();
                                    return $maxFaqs ?? null; // null = unlimited
                                })
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            $maxFaqs = app(NewBusinessPlanLimits::class)->maxFaqs();
                            if ($maxFaqs !== null && count($state ?? []) > $maxFaqs) {
                                \Filament\Notifications\Notification::make()
                                    ->warning()
                                    ->title('FAQ Limit Reached')
                                    ->body("Your plan allows a maximum of {$maxFaqs} FAQs. Please remove some FAQs or upgrade your plan.")
                                    ->send();
                                $set('faqs_temp', array_slice($state, 0, $maxFaqs));
                            }
                        })
                        ->columnSpanFull(),
                        ]),
                ])
                ->columns(1),
            
            // Step 5: Social Media (Optional)
            Wizard\Step::make('Social Media')
                ->description('Connect your social media profiles (optional - you can skip this step)')
                ->schema([
                    Forms\Components\Section::make('Social Media Profiles')
                        ->description('Add your social media accounts to increase your online presence')
                        ->schema([
                            Forms\Components\Repeater::make('social_accounts_temp')
                                ->label('')
                                ->schema([
                                    Forms\Components\Select::make('platform')
                                        ->label('Platform')
                                        ->options([
                                            'facebook' => 'Facebook',
                                            'instagram' => 'Instagram',
                                            'twitter' => 'Twitter (X)',
                                            'linkedin' => 'LinkedIn',
                                            'youtube' => 'YouTube',
                                            'tiktok' => 'TikTok',
                                        ])
                                        ->required()
                                        ->searchable()
                                        ->placeholder('Select platform'),
                                    
                                    Forms\Components\TextInput::make('url')
                                        ->label('Profile URL')
                                        ->url()
                                        ->required()
                                        ->maxLength(255)
                                        ->placeholder('https://facebook.com/yourbusiness'),
                                    
                                    Forms\Components\Toggle::make('is_active')
                                        ->label('Active')
                                        ->default(true)
                                        ->helperText('Show on business page'),
                                ])
                                ->columns(3)
                                ->defaultItems(0)
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => ucfirst($state['platform'] ?? 'New Account'))
                                ->addActionLabel('Add Social Account')
                                ->columnSpanFull(),
                        ]),
                ])
                ->columns(1),
            
            // Step 6: Team Members (Optional)
            Wizard\Step::make('Team Members')
                ->description('Add your team members and staff (optional - you can skip this step)')
                ->schema([
                    Forms\Components\Section::make('Team Members & Staff')
                        ->description(function () {
                            $maxTeamMembers = app(NewBusinessPlanLimits::class)->maxTeamMembers();
                            if ($maxTeamMembers === null) {
                                return 'Showcase your team members and staff (Unlimited)';
                            }
                            return "Showcase your team members and staff (Limit: {$maxTeamMembers} members)";
                        })
                        ->schema([
                            Forms\Components\Repeater::make('officials_temp')
                                ->label('')
                                ->schema([
                                    Forms\Components\FileUpload::make('photo')
                                        ->label('Profile Photo')
                                        ->image()
                                        ->directory('official-photos')
                                        ->maxSize(2048)
                                        ->imageEditor()
                                        ->avatar()
                                        ->helperText('Upload a professional photo')
                                        ->columnSpanFull(),
                                    
                                    Forms\Components\TextInput::make('name')
                                        ->label('Full Name')
                                        ->required()
                                        ->maxLength(255)
                                        ->placeholder('e.g., John Doe'),
                                    
                                    Forms\Components\TextInput::make('position')
                                        ->label('Job Title/Position')
                                        ->required()
                                        ->maxLength(255)
                                        ->placeholder('e.g., CEO, Manager, Chef')
                                        ->helperText('Their role in your business'),
                                    
                                    Forms\Components\TextInput::make('order')
                                        ->label('Display Order')
                                        ->numeric()
                                        ->default(0)
                                        ->placeholder('0')
                                        ->helperText('Lower numbers appear first'),
                                    
                                    Forms\Components\Toggle::make('is_active')
                                        ->label('Active')
                                        ->default(true)
                                        ->helperText('Show on business page'),
                                    
                                    Forms\Components\Repeater::make('social_accounts')
                                        ->label('Social Media (Optional)')
                                        ->schema([
                                            Forms\Components\Select::make('platform')
                                                ->label('Platform')
                                                ->options([
                                                    'linkedin' => 'LinkedIn',
                                                    'twitter' => 'Twitter (X)',
                                                    'facebook' => 'Facebook',
                                                    'instagram' => 'Instagram',
                                                    'youtube' => 'YouTube',
                                                    'tiktok' => 'TikTok',
                                                    'github' => 'GitHub',
                                                    'website' => 'Personal Website',
                                                ])
                                                ->required()
                                                ->placeholder('Select platform'),
                                            
                                            Forms\Components\TextInput::make('url')
                                                ->label('Profile URL')
                                                ->url()
                                                ->required()
                                                ->maxLength(255)
                                                ->placeholder('https://linkedin.com/in/username'),
                                        ])
                                        ->columns(2)
                                        ->defaultItems(0)
                                        ->collapsible()
                                        ->itemLabel(fn (array $state): ?string => ucfirst($state['platform'] ?? 'Social Link'))
                                        ->addActionLabel('Add Social Link')
                                        ->columnSpanFull(),
                                ])
                                ->columns(2)
                                ->defaultItems(0)
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'New Team Member')
                                ->addActionLabel('Add Team Member')
                                ->maxItems(function () {
                                    $maxTeamMembers = app(NewBusinessPlanLimits::class)->maxTeamMembers();
                                    return $maxTeamMembers ?? null;
                                })
                                ->columnSpanFull(),
                        ]),
                ])
                ->columns(1),
            
            // Step 7: Media & Branding (Optional)
            Wizard\Step::make('Media & Branding')
                ->description('Upload your business images (optional - you can skip this step)')
                ->schema([
                    Forms\Components\Section::make('Brand Assets')
                        ->description('Upload your logo and cover photo to build brand recognition')
                        ->schema([
                            Forms\Components\FileUpload::make('logo')
                                ->label('Business Logo')
                                ->image()
                                ->directory('business-logos')
                                ->maxSize(2048)
                                ->imageEditor()
                                ->helperText('Square logo works best (PNG with transparent background recommended)'),
                            
                            Forms\Components\FileUpload::make('cover_photo')
                                ->label('Cover Photo')
                                ->image()
                                ->directory('business-covers')
                                ->maxSize(5120)
                                ->imageEditor()
                                ->helperText('Wide banner image (1200x400px recommended)'),
                        ])
                        ->columns(2),
                    
                    Forms\Components\Section::make('Photo Gallery')
                        ->description(function () {
                            $maxPhotos = app(NewBusinessPlanLimits::class)->maxPhotos();
                            if ($maxPhotos === null) {
                                return 'Showcase your business with photos (Unlimited)';
                            }
                            return "Showcase your business with photos (Limit: {$maxPhotos} photos)";
                        })
                        ->schema([
                            Forms\Components\FileUpload::make('gallery')
                                ->label('Gallery Images')
                                ->image()
                                ->directory('business-gallery')
                                ->multiple()
                                ->maxFiles(function () {
                                    $maxPhotos = app(NewBusinessPlanLimits::class)->maxPhotos();
                                    return $maxPhotos ?? 50; // default if unlimited
                                })
                                ->maxSize(3072)
                                ->imageEditor()
                                ->reorderable()
                                ->appendFiles()
                                ->panelLayout('grid')
                                ->helperText(function () {
                                    $maxPhotos = app(NewBusinessPlanLimits::class)->maxPhotos();
                                    if ($maxPhotos === null) {
                                        return 'Upload photos of your business, products, or services';
                                    }
                                    return "Upload up to {$maxPhotos} photos of your business, products, or services";
                                })
                                ->columnSpanFull(),
                        ]),
                ])
                ->columns(1),
            
            // Step 8: SEO Settings (Optional) - LAST STEP
            Wizard\Step::make('SEO Settings')
                ->description('Search engine optimization (optional - you can skip this step)')
                ->schema([
                    Forms\Components\Section::make('Search Engine Optimization')
                        ->description('Optimize your business listing for search engines')
                        ->schema([
                            Forms\Components\Select::make('canonical_strategy')
                                ->label('Indexing Strategy')
                                ->options([
                                    'self' => 'Index Separately (Unique business with own SEO)',
                                    'parent' => 'Standard Business',
                                ])
                                ->default('self')
                                ->required()
                                ->helperText('Most businesses should use "Index Separately"'),
                            
                            Forms\Components\TextInput::make('meta_title')
                                ->label('SEO Title')
                                ->maxLength(255)
                                ->placeholder('e.g., Best Restaurant in Lagos | Your Business Name')
                                ->helperText('Custom page title (auto-generated if empty)'),
                            
                            Forms\Components\Textarea::make('meta_description')
                                ->label('SEO Description')
                                ->maxLength(255)
                                ->rows(3)
                                ->placeholder('Describe your business in a way that appears in search results...')
                                ->helperText('Custom meta description (auto-generated if empty)'),
                            
                            Forms\Components\TagsInput::make('unique_features')
                                ->label('Unique Features')
                                ->helperText('What makes your business unique? Press Enter after each feature')
                                ->placeholder('e.g., 24/7 Service, Award Winning, Free Delivery')
                                ->columnSpanFull(),
                            
                            Forms\Components\Textarea::make('nearby_landmarks')
                                ->label('Nearby Landmarks')
                                ->rows(3)
                                ->placeholder('e.g., Near Ikeja City Mall, Opposite GTBank, Behind National Stadium...')
                                ->helperText('Mention nearby landmarks to help customers find you')
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                ])
                ->columns(1),
        ];
    }
    
    // ✅ FIXED: Transform business_hours and handle relationships
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set ownership
        $data['user_id'] = Auth::id();
        $data['status'] = 'pending_review';
        $data['is_claimed'] = false;
        $data['claimed_by'] = null;
        
        // ✅ FIXED: Transform business_hours from individual fields to keyed array
        $businessHours = [];
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        
        foreach ($days as $day) {
            $openKey = "{$day}_open";
            $closeKey = "{$day}_close";
            $closedKey = "{$day}_closed";
            
            if (isset($data[$closedKey]) && $data[$closedKey]) {
                $businessHours[$day] = [
                    'open' => null,
                    'close' => null,
                    'closed' => true,
                ];
            } elseif (isset($data[$openKey]) || isset($data[$closeKey])) {
                $businessHours[$day] = [
                    'open' => $data[$openKey] ?? null,
                    'close' => $data[$closeKey] ?? null,
                    'closed' => false,
                ];
            }
            
            // Remove individual fields from data
            unset($data[$openKey], $data[$closeKey], $data[$closedKey]);
        }
        
        if (!empty($businessHours)) {
            $data['business_hours'] = $businessHours;
        }
        
        // ✅ FIXED: Extract relationship data before creation
        $categories = $data['categories'] ?? [];
        $paymentMethods = $data['payment_methods'] ?? [];
        $amenities = $data['amenities'] ?? [];
        $faqs = $data['faqs_temp'] ?? [];
        $socialAccounts = $data['social_accounts_temp'] ?? [];
        $officials = $data['officials_temp'] ?? [];
        
        // Remove from data array (will be synced after creation)
        unset($data['categories'], $data['payment_methods'], $data['amenities'], $data['faqs_temp'], $data['social_accounts_temp'], $data['officials_temp']);
        
        // Store for after creation hook
        $this->categoriesData = $categories;
        $this->paymentMethodsData = $paymentMethods;
        $this->amenitiesData = $amenities;
        $this->faqsData = $faqs;
        $this->socialAccountsData = $socialAccounts;
        $this->officialsData = $officials;
        
        return $data;
    }
    
    // ✅ FIXED: Sync relationships after business is created
    protected function afterCreate(): void
    {
        $business = $this->record;
        $user = Auth::user();

        // Assign free plan to new business (immediately after create)
        $freePlan = app(NewBusinessPlanLimits::class)->plan();
        $subscription = null;
        if ($freePlan) {
            $subscription = Subscription::create([
                'business_id' => $business->id,
                'user_id' => $user->id,
                'subscription_plan_id' => $freePlan->id,
                'billing_interval' => 'yearly',
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => now()->addYear(),
                'auto_renew' => true,
            ]);
        }

        // Sync categories
        if (!empty($this->categoriesData)) {
            $business->categories()->sync($this->categoriesData);
        }

        // Sync payment methods
        if (!empty($this->paymentMethodsData)) {
            $business->paymentMethods()->sync($this->paymentMethodsData);
        }

        // Sync amenities
        if (!empty($this->amenitiesData)) {
            $business->amenities()->sync($this->amenitiesData);
        }

        // Create FAQs (shadow limits: free plan)
        if (!empty($this->faqsData)) {
            $maxFaqs = app(NewBusinessPlanLimits::class)->maxFaqs();
            if ($maxFaqs !== null && count($this->faqsData) > $maxFaqs) {
                \Filament\Notifications\Notification::make()
                    ->warning()
                    ->title('FAQ Limit Exceeded')
                    ->body("Your plan allows a maximum of {$maxFaqs} FAQs. Only the first {$maxFaqs} FAQs were saved.")
                    ->send();
                $this->faqsData = array_slice($this->faqsData, 0, $maxFaqs);
            }
            foreach ($this->faqsData as $faqData) {
                FAQ::create([
                    'business_id' => $business->id,
                    'question' => $faqData['question'],
                    'answer' => $faqData['answer'],
                    'order' => $faqData['order'] ?? 0,
                    'is_active' => $faqData['is_active'] ?? true,
                ]);
            }
            if ($subscription) {
                $subscription->update(['faqs_used' => count($this->faqsData)]);
            }
        }

        if ($subscription && $business->gallery) {
            $subscription->update(['photos_used' => count($business->gallery)]);
        }

        // Create Social Accounts
        if (!empty($this->socialAccountsData)) {
            foreach ($this->socialAccountsData as $socialData) {
                SocialAccount::create([
                    'business_id' => $business->id,
                    'platform' => $socialData['platform'],
                    'url' => $socialData['url'],
                    'is_active' => $socialData['is_active'] ?? true,
                ]);
            }
        }
        
        // Create Officials/Team Members
        if (!empty($this->officialsData)) {
            foreach ($this->officialsData as $officialData) {
                Official::create([
                    'business_id' => $business->id,
                    'name' => $officialData['name'],
                    'position' => $officialData['position'],
                    'photo' => $officialData['photo'] ?? null,
                    'order' => $officialData['order'] ?? 0,
                    'is_active' => $officialData['is_active'] ?? true,
                    'social_accounts' => $officialData['social_accounts'] ?? [],
                ]);
            }
            if ($subscription) {
                $subscription->update(['team_members_used' => count($this->officialsData)]);
            }
        }

        app(ActiveBusiness::class)->setActiveBusinessId($business->id);
    }
    
    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Business created successfully! It will be reviewed by our team.';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
    
    // ✅ ADDED: Store relationship data temporarily
    protected array $categoriesData = [];
    protected array $paymentMethodsData = [];
    protected array $amenitiesData = [];
    protected array $faqsData = [];
    protected array $socialAccountsData = [];
    protected array $officialsData = [];
    
    /**
     * Add Google Places Autocomplete JavaScript to the page
     */
    protected function getHeaderActions(): array
    {
        // This will render our custom view with the Google Places script
        return [];
    }
    
    public function getFooter(): string
    {
        return view('filament.widgets.google-places-autocomplete')->render();
    }
}
