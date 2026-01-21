<?php
// ============================================
// app/Filament/Business/Resources/BusinessResource/Pages/EditBusiness.php
// FIXED VERSION - Matches CreateBusiness pattern
// ============================================

namespace App\Filament\Business\Resources\BusinessResource\Pages;

use App\Filament\Business\Resources\BusinessResource;
use App\Models\BusinessType;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Amenity;
use App\Models\Location;
use App\Models\FAQ;
use App\Models\SocialAccount;
use App\Models\Official;
use Illuminate\Support\Facades\Auth;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\EditRecord\Concerns\HasWizard;

class EditBusiness extends EditRecord
{
    use HasWizard;
    
    protected static string $resource = BusinessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
    
    public function hasSkippableSteps(): bool
    {
        return true;
    }
    
    protected function getSteps(): array
    {
        return [
            // Step 1: Basic Information
            Wizard\Step::make('Basic Information')
                ->description('Enter your business name and type')
                ->schema([
                    Forms\Components\TextInput::make('business_name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, Forms\Set $set, $old) => 
                            $old !== $state ? $set('slug', \Illuminate\Support\Str::slug($state)) : null
                        ),
                    
                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->disabled()
                        ->dehydrated()
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
                        ->helperText('Describe your business in detail')
                        ->columnSpanFull(),
                ])
                ->columns(2),
            
            // Step 2: Location & Contact
            Wizard\Step::make('Location & Contact')
                ->description('Where is your business located?')
                ->schema([
                    Forms\Components\Select::make('state_location_id')
                        ->label('State')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->options(function () {
                            return Location::where('type', 'state')
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id');
                        })
                        ->live()
                        ->afterStateUpdated(function (Forms\Set $set, $state) {
                            $set('city_location_id', null);
                            $stateName = Location::find($state)?->name;
                            $set('state', $stateName);
                        }),
                    
                    Forms\Components\Select::make('city_location_id')
                        ->label('City')
                        ->required()
                        ->searchable()
                        ->preload()
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
                            $cityName = Location::find($state)?->name;
                            $set('city', $cityName);
                        }),
                    
                    Forms\Components\Hidden::make('state'),
                    Forms\Components\Hidden::make('city'),
                    
                    Forms\Components\TextInput::make('address')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull()
                        ->helperText('Street address or building name'),
                    
                    Forms\Components\TextInput::make('area')
                        ->label('Area/Neighborhood')
                        ->maxLength(100)
                        ->helperText('Optional: Specific area or neighborhood'),
                    
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('latitude')
                                ->numeric()
                                ->step(0.0000001)
                                ->helperText('Optional: For map display'),
                            
                            Forms\Components\TextInput::make('longitude')
                                ->numeric()
                                ->step(0.0000001)
                                ->helperText('Optional: For map display'),
                        ]),
                    
                    Forms\Components\Section::make('Contact Information')
                        ->schema([
                            Forms\Components\TextInput::make('phone')
                                ->tel()
                                ->maxLength(20),
                            
                            Forms\Components\TextInput::make('email')
                                ->email()
                                ->maxLength(255),
                            
                            Forms\Components\TextInput::make('whatsapp')
                                ->tel()
                                ->maxLength(20),
                            
                            Forms\Components\TextInput::make('website')
                                ->url()
                                ->maxLength(255),
                            
                            Forms\Components\Textarea::make('whatsapp_message')
                                ->maxLength(500)
                                ->helperText('Pre-filled message when customers click WhatsApp')
                                ->rows(3),
                        ])
                        ->columns(2),
                ])
                ->columns(2),
            
            // Step 3: Business Hours (Optional)
            Wizard\Step::make('Business Hours')
                ->description('Set your operating hours (optional - you can skip this step)')
                ->schema([
                    Forms\Components\Repeater::make('business_hours_temp')
                        ->label('Business Hours')
                        ->schema([
                            Forms\Components\Select::make('day')
                                ->options([
                                    'monday' => 'Monday',
                                    'tuesday' => 'Tuesday',
                                    'wednesday' => 'Wednesday',
                                    'thursday' => 'Thursday',
                                    'friday' => 'Friday',
                                    'saturday' => 'Saturday',
                                    'sunday' => 'Sunday',
                                ])
                                ->required(),
                            
                            Forms\Components\TimePicker::make('open')
                                ->required(),
                            
                            Forms\Components\TimePicker::make('close')
                                ->required(),
                            
                            Forms\Components\Toggle::make('closed')
                                ->label('Closed this day')
                                ->default(false),
                        ])
                        ->columns(4)
                        ->defaultItems(0)
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => ucfirst($state['day'] ?? 'New Day'))
                        ->helperText('Add your operating hours for each day'),
                ])
                ->columns(1),
            
            // Step 4: Features & Amenities (Optional)
            Wizard\Step::make('Features & Amenities')
                ->description('What facilities do you offer? (optional - you can skip this step)')
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
                ->columns(1),
            
            // Step 5: Legal Information (Optional)
            Wizard\Step::make('Legal Information')
                ->description('Business registration details (optional - you can skip this step)')
                ->schema([
                    Forms\Components\TextInput::make('registration_number')
                        ->label('CAC/RC Number')
                        ->maxLength(50)
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
                ->columns(3),
            
            // Step 6: FAQs (Optional)
            Wizard\Step::make('FAQs')
                ->description('Add frequently asked questions (optional - you can skip this step)')
                ->schema([
                    Forms\Components\Repeater::make('faqs_temp')
                        ->label('Frequently Asked Questions')
                        ->schema([
                            Forms\Components\TextInput::make('question')
                                ->label('Question')
                                ->required()
                                ->maxLength(255)
                                ->columnSpanFull(),
                            
                            Forms\Components\Textarea::make('answer')
                                ->label('Answer')
                                ->required()
                                ->rows(3)
                                ->maxLength(1000)
                                ->columnSpanFull(),
                            
                            Forms\Components\Toggle::make('is_active')
                                ->label('Active')
                                ->default(true),
                            
                            Forms\Components\TextInput::make('order')
                                ->label('Order')
                                ->numeric()
                                ->default(0)
                                ->helperText('Display order (lower numbers appear first)'),
                        ])
                        ->columns(2)
                        ->defaultItems(0)
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['question'] ?? 'New FAQ')
                        ->helperText(function () {
                            $user = Auth::user();
                            $subscription = $user->subscription;
                            $maxFaqs = $subscription?->plan?->max_faqs;
                            $currentCount = $this->record->faqs()->count();
                            
                            if ($maxFaqs === null) {
                                return "Add frequently asked questions about your business (Unlimited - {$currentCount} added)";
                            }
                            
                            return "Add frequently asked questions about your business ({$currentCount} / {$maxFaqs} used)";
                        })
                        ->maxItems(function () {
                            $user = Auth::user();
                            $subscription = $user->subscription;
                            $maxFaqs = $subscription?->plan?->max_faqs;
                            
                            if ($maxFaqs === null) {
                                return null; // Unlimited
                            }
                            
                            return $maxFaqs;
                        })
                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                            // Validate FAQ limit
                            $user = Auth::user();
                            $subscription = $user->subscription;
                            $maxFaqs = $subscription?->plan?->max_faqs;
                            
                            if ($maxFaqs !== null && count($state ?? []) > $maxFaqs) {
                                \Filament\Notifications\Notification::make()
                                    ->warning()
                                    ->title('FAQ Limit Reached')
                                    ->body("Your plan allows a maximum of {$maxFaqs} FAQs. Please remove some FAQs or upgrade your plan.")
                                    ->send();
                                
                                // Trim to max
                                $set('faqs_temp', array_slice($state, 0, $maxFaqs));
                            }
                        })
                        ->columnSpanFull(),
                ])
                ->columns(1),
            
            // Step 7: Social Media (Optional)
            Wizard\Step::make('Social Media')
                ->description('Connect your social media profiles (optional - you can skip this step)')
                ->schema([
                    Forms\Components\Repeater::make('social_accounts_temp')
                        ->label('Social Media Accounts')
                        ->schema([
                            Forms\Components\Select::make('platform')
                                ->options([
                                    'facebook' => 'Facebook',
                                    'instagram' => 'Instagram',
                                    'twitter' => 'Twitter (X)',
                                    'linkedin' => 'LinkedIn',
                                    'youtube' => 'YouTube',
                                    'tiktok' => 'TikTok',
                                    'pinterest' => 'Pinterest',
                                    'whatsapp' => 'WhatsApp Business',
                                ])
                                ->required()
                                ->searchable(),
                            
                            Forms\Components\TextInput::make('url')
                                ->label('Profile URL')
                                ->url()
                                ->required()
                                ->maxLength(255)
                                ->placeholder('https://facebook.com/yourbusiness'),
                            
                            Forms\Components\Toggle::make('is_active')
                                ->label('Active')
                                ->default(true),
                        ])
                        ->columns(3)
                        ->defaultItems(0)
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => ucfirst($state['platform'] ?? 'New Account'))
                        ->helperText('Add your social media profiles to increase your online presence')
                        ->columnSpanFull(),
                ])
                ->columns(1),
            
            // Step 8: Team Members (Optional)
            Wizard\Step::make('Team Members')
                ->description('Add your team members and staff (optional - you can skip this step)')
                ->schema([
                    Forms\Components\Repeater::make('officials_temp')
                        ->label('Team Members')
                        ->schema([
                            Forms\Components\FileUpload::make('photo')
                                ->image()
                                ->directory('official-photos')
                                ->maxSize(2048)
                                ->imageEditor()
                                ->avatar()
                                ->columnSpanFull(),
                            
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            
                            Forms\Components\TextInput::make('position')
                                ->required()
                                ->maxLength(255)
                                ->helperText('e.g., CEO, Manager, Chef, etc.'),
                            
                            Forms\Components\TextInput::make('order')
                                ->label('Display Order')
                                ->numeric()
                                ->default(0)
                                ->helperText('Lower numbers appear first'),
                            
                            Forms\Components\Toggle::make('is_active')
                                ->label('Active')
                                ->default(true),
                            
                            Forms\Components\Repeater::make('social_accounts')
                                ->label('Social Media')
                                ->schema([
                                    Forms\Components\Select::make('platform')
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
                                        ->required(),
                                    
                                    Forms\Components\TextInput::make('url')
                                        ->url()
                                        ->required()
                                        ->maxLength(255),
                                ])
                                ->columns(2)
                                ->defaultItems(0)
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['platform'] ?? null)
                                ->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->defaultItems(0)
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'New Team Member')
                        ->helperText('Add your team members to showcase your business team')
                        ->columnSpanFull(),
                ])
                ->columns(1),
            
            // Step 9: Media & Branding (Optional)
            Wizard\Step::make('Media & Branding')
                ->description('Upload your business images (optional - you can skip this step)')
                ->schema([
                    Forms\Components\FileUpload::make('logo')
                        ->image()
                        ->directory('business-logos')
                        ->maxSize(2048)
                        ->imageEditor()
                        ->helperText('Square logo works best'),
                    
                    Forms\Components\FileUpload::make('cover_photo')
                        ->image()
                        ->directory('business-covers')
                        ->maxSize(5120)
                        ->imageEditor()
                        ->helperText('Wide banner image'),
                    
                    Forms\Components\FileUpload::make('gallery')
                        ->image()
                        ->directory('business-gallery')
                        ->multiple()
                        ->maxFiles(10)
                        ->maxSize(3072)
                        ->imageEditor()
                        ->helperText('Upload up to 10 images')
                        ->columnSpanFull(),
                ])
                ->columns(2),
            
            // Step 10: SEO Settings (Optional) - LAST STEP
            Wizard\Step::make('SEO Settings')
                ->description('Search engine optimization (optional - you can skip this step)')
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
                        ->maxLength(255)
                        ->helperText('Custom page title (auto-generated if empty)'),
                    
                    Forms\Components\Textarea::make('meta_description')
                        ->maxLength(255)
                        ->rows(3)
                        ->helperText('Custom meta description (auto-generated if empty)'),
                    
                    Forms\Components\TagsInput::make('unique_features')
                        ->helperText('What makes your business unique? (e.g., "24/7 Service", "Award Winning")')
                        ->placeholder('Add unique features'),
                    
                    Forms\Components\Textarea::make('nearby_landmarks')
                        ->rows(3)
                        ->helperText('Mention nearby landmarks to help customers find you'),
                ])
                ->columns(1),
        ];
    }
    
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load relationship data
        $data['categories'] = $this->record->categories()->pluck('categories.id')->toArray();
        $data['payment_methods'] = $this->record->paymentMethods()->pluck('payment_methods.id')->toArray();
        $data['amenities'] = $this->record->amenities()->pluck('amenities.id')->toArray();
        
        // Load FAQs
        $data['faqs_temp'] = $this->record->faqs()->get()->map(function ($faq) {
            return [
                'id' => $faq->id,
                'question' => $faq->question,
                'answer' => $faq->answer,
                'order' => $faq->order,
                'is_active' => $faq->is_active,
            ];
        })->toArray();
        
        // Load Social Accounts
        $data['social_accounts_temp'] = $this->record->socialAccounts()->get()->map(function ($account) {
            return [
                'id' => $account->id,
                'platform' => $account->platform,
                'url' => $account->url,
                'is_active' => $account->is_active,
            ];
        })->toArray();
        
        // Load Officials/Team Members
        $data['officials_temp'] = $this->record->officials()->get()->map(function ($official) {
            return [
                'id' => $official->id,
                'name' => $official->name,
                'position' => $official->position,
                'photo' => $official->photo,
                'order' => $official->order,
                'is_active' => $official->is_active,
                'social_accounts' => $official->social_accounts ?? [],
            ];
        })->toArray();
        
        // Transform business_hours from keyed array to repeater format for editing
        if (isset($data['business_hours']) && is_array($data['business_hours'])) {
            $businessHoursTemp = [];
            foreach ($data['business_hours'] as $day => $hours) {
                $businessHoursTemp[] = [
                    'day' => $day,
                    'open' => $hours['open'] ?? null,
                    'close' => $hours['close'] ?? null,
                    'closed' => $hours['closed'] ?? false,
                ];
            }
            $data['business_hours_temp'] = $businessHoursTemp;
        }
        
        return $data;
    }
    
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Transform business_hours from repeater to keyed array
        if (isset($data['business_hours_temp']) && is_array($data['business_hours_temp'])) {
            $businessHours = [];
            foreach ($data['business_hours_temp'] as $hours) {
                if (isset($hours['day'])) {
                    $businessHours[$hours['day']] = [
                        'open' => $hours['open'] ?? null,
                        'close' => $hours['close'] ?? null,
                        'closed' => $hours['closed'] ?? false,
                    ];
                }
            }
            $data['business_hours'] = $businessHours;
            unset($data['business_hours_temp']);
        }
        
        // Extract relationship data
        $categories = $data['categories'] ?? [];
        $paymentMethods = $data['payment_methods'] ?? [];
        $amenities = $data['amenities'] ?? [];
        $faqs = $data['faqs_temp'] ?? [];
        $socialAccounts = $data['social_accounts_temp'] ?? [];
        $officials = $data['officials_temp'] ?? [];
        
        unset($data['categories'], $data['payment_methods'], $data['amenities'], $data['faqs_temp'], $data['social_accounts_temp'], $data['officials_temp']);
        
        $this->categoriesData = $categories;
        $this->paymentMethodsData = $paymentMethods;
        $this->amenitiesData = $amenities;
        $this->faqsData = $faqs;
        $this->socialAccountsData = $socialAccounts;
        $this->officialsData = $officials;
        
        return $data;
    }
    
    protected function afterSave(): void
    {
        $business = $this->record;
        
        // Sync categories
        if (isset($this->categoriesData)) {
            $business->categories()->sync($this->categoriesData);
        }
        
        // Sync payment methods
        if (isset($this->paymentMethodsData)) {
            $business->paymentMethods()->sync($this->paymentMethodsData);
        }
        
        // Sync amenities
        if (isset($this->amenitiesData)) {
            $business->amenities()->sync($this->amenitiesData);
        }
        
        // Handle FAQs - Delete existing and create new ones (with limit check)
        if (isset($this->faqsData)) {
            $user = Auth::user();
            $subscription = $user->subscription;
            $maxFaqs = $subscription?->plan?->max_faqs;
            
            // Count existing FAQs from other businesses
            $otherBusinessesFaqs = $user->businesses()
                ->where('id', '!=', $business->id)
                ->withCount('faqs')
                ->get()
                ->sum('faqs_count');
            
            // Calculate how many FAQs this business can have
            $allowedForThisBusiness = $maxFaqs !== null 
                ? max(0, $maxFaqs - $otherBusinessesFaqs)
                : null;
            
            // Enforce limit
            if ($allowedForThisBusiness !== null && count($this->faqsData) > $allowedForThisBusiness) {
                \Filament\Notifications\Notification::make()
                    ->warning()
                    ->title('FAQ Limit Exceeded')
                    ->body("Your plan allows a maximum of {$maxFaqs} FAQs total. You have {$otherBusinessesFaqs} FAQs in other businesses. Only the first {$allowedForThisBusiness} FAQs were saved.")
                    ->send();
                
                $this->faqsData = array_slice($this->faqsData, 0, $allowedForThisBusiness);
            }
            
            // Get IDs of FAQs that should be kept
            $faqIdsToKeep = collect($this->faqsData)->pluck('id')->filter()->toArray();
            
            // Delete FAQs that are not in the new list
            $business->faqs()->whereNotIn('id', $faqIdsToKeep)->delete();
            
            // Update or create FAQs
            foreach ($this->faqsData as $faqData) {
                if (isset($faqData['id']) && $faqData['id']) {
                    // Update existing FAQ
                    FAQ::where('id', $faqData['id'])
                        ->where('business_id', $business->id)
                        ->update([
                            'question' => $faqData['question'],
                            'answer' => $faqData['answer'],
                            'order' => $faqData['order'] ?? 0,
                            'is_active' => $faqData['is_active'] ?? true,
                        ]);
                } else {
                    // Create new FAQ
                    FAQ::create([
                        'business_id' => $business->id,
                        'question' => $faqData['question'],
                        'answer' => $faqData['answer'],
                        'order' => $faqData['order'] ?? 0,
                        'is_active' => $faqData['is_active'] ?? true,
                    ]);
                }
            }
            
            // Update subscription usage
            if ($subscription) {
                $totalFaqs = $user->businesses()->withCount('faqs')->get()->sum('faqs_count');
                $subscription->update(['faqs_used' => $totalFaqs]);
            }
        }
        
        // Handle Social Accounts - Delete existing and create new ones
        if (isset($this->socialAccountsData)) {
            // Get IDs of social accounts that should be kept
            $accountIdsToKeep = collect($this->socialAccountsData)->pluck('id')->filter()->toArray();
            
            // Delete social accounts that are not in the new list
            $business->socialAccounts()->whereNotIn('id', $accountIdsToKeep)->delete();
            
            // Update or create social accounts
            foreach ($this->socialAccountsData as $accountData) {
                if (isset($accountData['id']) && $accountData['id']) {
                    // Update existing account
                    SocialAccount::where('id', $accountData['id'])
                        ->where('business_id', $business->id)
                        ->update([
                            'platform' => $accountData['platform'],
                            'url' => $accountData['url'],
                            'is_active' => $accountData['is_active'] ?? true,
                        ]);
                } else {
                    // Create new account
                    SocialAccount::create([
                        'business_id' => $business->id,
                        'platform' => $accountData['platform'],
                        'url' => $accountData['url'],
                        'is_active' => $accountData['is_active'] ?? true,
                    ]);
                }
            }
        }
        
        // Handle Officials/Team Members - Delete existing and create new ones
        if (isset($this->officialsData)) {
            // Get IDs of officials that should be kept
            $officialIdsToKeep = collect($this->officialsData)->pluck('id')->filter()->toArray();
            
            // Delete officials that are not in the new list
            $business->officials()->whereNotIn('id', $officialIdsToKeep)->delete();
            
            // Update or create officials
            foreach ($this->officialsData as $officialData) {
                if (isset($officialData['id']) && $officialData['id']) {
                    // Update existing official
                    Official::where('id', $officialData['id'])
                        ->where('business_id', $business->id)
                        ->update([
                            'name' => $officialData['name'],
                            'position' => $officialData['position'],
                            'photo' => $officialData['photo'] ?? null,
                            'order' => $officialData['order'] ?? 0,
                            'is_active' => $officialData['is_active'] ?? true,
                            'social_accounts' => $officialData['social_accounts'] ?? [],
                        ]);
                } else {
                    // Create new official
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
            }
        }
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
    
    protected ?array $categoriesData = null;
    protected ?array $paymentMethodsData = null;
    protected ?array $amenitiesData = null;
    protected ?array $faqsData = null;
    protected ?array $socialAccountsData = null;
    protected ?array $officialsData = null;
}
