<?php
// ============================================
// app/Livewire/CreateGuestBusiness.php
// Guest Business Creation with Wizard & Abandoned Form Tracking
// ============================================

namespace App\Livewire;

use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Amenity;
use App\Models\Location;
use App\Models\GuestBusinessDraft;
use App\Services\NewBusinessPlanLimits;
use Filament\Forms;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

#[Layout('layouts.business')]
class CreateGuestBusiness extends Component implements HasForms
{
    use InteractsWithForms;
    
    public ?array $data = [];
    public ?string $guestEmail = null;
    public ?string $guestPhone = null;
    public ?int $draftId = null;
    public int $currentStep = 1;
    
    protected NewBusinessPlanLimits $planLimits;
    
    public function mount()
    {
        $this->planLimits = app(NewBusinessPlanLimits::class);
        
        // Support resuming via ?resume={id} (used in reminder emails)
        $resumeId = request()->query('resume');
        if ($resumeId) {
            session()->put('guest_draft_id', (int)$resumeId);
        }

        // Check if resuming a draft from session
        if (session()->has('guest_draft_id')) {
            $this->loadDraft(session('guest_draft_id'));
        } else {
            $this->form->fill();
        }
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
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
                                            $set('slug', Str::slug($state))
                                        ),
                                    
                                    Forms\Components\TextInput::make('slug')
                                        ->required()
                                        ->maxLength(255)
                                        ->unique(Business::class, ignoreRecord: true)
                                        ->disabled()
                                        ->dehydrated()
                                        ->placeholder('auto-generated-from-business-name')
                                        ->helperText('URL-friendly version of your business name (auto-generated)'),
                                    
                                    Forms\Components\Select::make('business_type_id')
                                        ->label('Business Type')
                                        ->required()
                                        ->searchable()
                                        ->preload()
                                        ->options(function () {
                                            return BusinessType::where('is_active', true)
                                                ->orderBy('name')
                                                ->pluck('name', 'id');
                                        })
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
                        ->columns(1)
                        ->afterValidation(function () {
                            $this->saveDraft(1);
                        }),
                    
                    // Step 2: Location & Contact
                    Wizard\Step::make('Location & Contact')
                        ->description('Where is your business located and how can customers reach you?')
                        ->schema([
                            Forms\Components\Section::make('Business Location')
                                ->description('Provide your physical business address')
                                ->schema([
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
                                            $stateName = Location::find($state)?->name;
                                            $set('state', $stateName);
                                        }),
                                    
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
                                            $cityName = Location::find($state)?->name;
                                            $set('city', $cityName);
                                        }),
                                    
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
                                        ->placeholder('+234 800 123 4567')
                                        ->required(),
                                    
                                    Forms\Components\TextInput::make('email')
                                        ->label('Email Address')
                                        ->email()
                                        ->maxLength(255)
                                        ->placeholder('contact@yourbusiness.com')
                                        ->required()
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($state) {
                                            $this->guestEmail = $state;
                                        }),
                                    
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
                        ->columns(1)
                        ->afterValidation(function () {
                            $this->saveDraft(2);
                        }),
                    
                    // Step 3: Business Hours
                    Wizard\Step::make('Business Hours')
                        ->description('Set your operating hours (Monday-Friday required, weekend optional)')
                        ->schema($this->getBusinessHoursSchema())
                        ->columns(1)
                        ->afterValidation(function () {
                            $this->saveDraft(3);
                        }),
                    
                    // Step 4: Review & Submit
                    Wizard\Step::make('Review & Submit')
                        ->description('Review your business information and create your listing')
                        ->schema([
                            Forms\Components\Placeholder::make('info')
                                ->content('Please review all the information you\'ve entered. Once you submit, your business listing will be created as a draft and you\'ll need to login or create an account to publish it.')
                                ->columnSpanFull(),
                            
                            Forms\Components\Checkbox::make('terms')
                                ->label('I agree to the Terms of Service and Privacy Policy')
                                ->required()
                                ->accepted(),
                        ]),
                ])
                ->submitAction(new \Illuminate\Support\HtmlString('
                    <button type="submit" class="filament-button filament-button-size-md inline-flex items-center justify-center py-1 gap-1 font-medium rounded-lg border transition-colors outline-none focus:ring-offset-2 focus:ring-2 focus:ring-inset min-h-[2.25rem] px-4 text-sm text-white shadow focus:ring-white border-transparent bg-primary-600 hover:bg-primary-500 focus:bg-primary-700 focus:ring-offset-primary-700">
                        Create Business Listing
                    </button>
                '))
                ->persistStepInQueryString()
                ->startOnStep($this->currentStep),
            ])
            ->statePath('data');
    }
    
    protected function getBusinessHoursSchema(): array
    {
        $days = [
            'monday' => 'Monday',
            'tuesday' => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday' => 'Thursday',
            'friday' => 'Friday',
            'saturday' => 'Saturday',
            'sunday' => 'Sunday',
        ];
        
        $weekdaySchema = [];
        $weekendSchema = [];
        
        foreach ($days as $key => $label) {
            $isWeekend = in_array($key, ['saturday', 'sunday']);
            
            $daySchema = Forms\Components\Grid::make(4)
                ->schema([
                    Forms\Components\Placeholder::make("{$key}_label")
                        ->label('')
                        ->content($label),
                    
                    Forms\Components\TimePicker::make("{$key}_open")
                        ->label('Opens')
                        ->required(fn (Forms\Get $get): bool => !$get("{$key}_closed"))
                        ->disabled(fn (Forms\Get $get) => $get("{$key}_closed")),
                    
                    Forms\Components\TimePicker::make("{$key}_close")
                        ->label('Closes')
                        ->required(fn (Forms\Get $get): bool => !$get("{$key}_closed"))
                        ->disabled(fn (Forms\Get $get) => $get("{$key}_closed")),
                    
                    Forms\Components\Toggle::make("{$key}_closed")
                        ->label('Closed')
                        ->default($isWeekend)
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set) use ($key) {
                            if ($state) {
                                $set("{$key}_open", null);
                                $set("{$key}_close", null);
                            }
                        }),
                ]);
            
            if ($isWeekend) {
                $weekendSchema[] = $daySchema;
            } else {
                $weekdaySchema[] = $daySchema;
            }
        }
        
        return [
            Forms\Components\Section::make('Weekdays (Required)')
                ->description('Please specify your operating hours for Monday through Friday')
                ->schema($weekdaySchema),
            
            Forms\Components\Section::make('Weekend (Optional)')
                ->description('Optionally set your weekend hours')
                ->schema($weekendSchema)
                ->collapsible()
                ->collapsed(),
        ];
    }
    
    protected function saveDraft(int $step)
    {
        try {
            $formData = $this->form->getState();
            
            // Store current step
            $this->currentStep = $step;
            
            // Create or update draft
            if ($this->draftId) {
                $draft = GuestBusinessDraft::find($this->draftId);
                if ($draft) {
                    $draft->update([
                        'form_data' => $formData,
                        'current_step' => $step,
                        'guest_email' => $this->guestEmail ?? $formData['email'] ?? null,
                        'guest_phone' => $this->guestPhone ?? $formData['phone'] ?? null,
                        'last_activity_at' => now(),
                    ]);
                }
            } else {
                $draft = GuestBusinessDraft::create([
                    'form_data' => $formData,
                    'current_step' => $step,
                    'guest_email' => $this->guestEmail ?? $formData['email'] ?? null,
                    'guest_phone' => $this->guestPhone ?? $formData['phone'] ?? null,
                    'session_id' => session()->getId(),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'last_activity_at' => now(),
                ]);
                
                $this->draftId = $draft->id;
                session()->put('guest_draft_id', $draft->id);
            }
            
        } catch (\Exception $e) {
            \Log::error('Failed to save guest draft: ' . $e->getMessage());
        }
    }
    
    protected function loadDraft(int $draftId)
    {
        try {
            $draft = GuestBusinessDraft::find($draftId);
            
            if ($draft && !$draft->is_converted) {
                $this->draftId = $draft->id;
                $this->currentStep = $draft->current_step;
                $this->guestEmail = $draft->guest_email;
                $this->guestPhone = $draft->guest_phone;
                
                $this->form->fill($draft->form_data);
                
                // Update last activity
                $draft->update(['last_activity_at' => now()]);
                
                Notification::make()
                    ->title('Draft Resumed')
                    ->body('Your previous progress has been restored.')
                    ->success()
                    ->send();
            }
        } catch (\Exception $e) {
            \Log::error('Failed to load guest draft: ' . $e->getMessage());
        }
    }
    
    public function submit()
    {
        try {
            $data = $this->form->getState();
            
            DB::beginTransaction();
            
            // Prepare business hours
            $businessHours = [];
            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            
            foreach ($days as $day) {
                $businessHours[$day] = [
                    'open' => $data["{$day}_open"] ?? null,
                    'close' => $data["{$day}_close"] ?? null,
                    'closed' => $data["{$day}_closed"] ?? false,
                ];
                
                // Remove individual day fields
                unset($data["{$day}_open"], $data["{$day}_close"], $data["{$day}_closed"]);
            }
            
            // Create the business as a draft
            $business = Business::create([
                'business_name' => $data['business_name'],
                'slug' => $data['slug'],
                'business_type_id' => $data['business_type_id'],
                'description' => $data['description'],
                'state_location_id' => $data['state_location_id'],
                'city_location_id' => $data['city_location_id'],
                'state' => $data['state'],
                'city' => $data['city'],
                'area' => $data['area'] ?? null,
                'address' => $data['address'],
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'phone' => $data['phone'],
                'email' => $data['email'],
                'whatsapp' => $data['whatsapp'] ?? null,
                'website' => $data['website'] ?? null,
                'whatsapp_message' => $data['whatsapp_message'] ?? null,
                'registration_number' => $data['registration_number'] ?? null,
                'entity_type' => $data['entity_type'] ?? null,
                'years_in_business' => $data['years_in_business'],
                'business_hours' => $businessHours,
                'status' => 'draft', // Keep as draft until user logs in
                'user_id' => null, // No user yet
            ]);
            
            // Attach relationships
            if (!empty($data['categories'])) {
                $business->categories()->attach($data['categories']);
            }
            
            if (!empty($data['payment_methods'])) {
                $business->paymentMethods()->attach($data['payment_methods']);
            }
            
            if (!empty($data['amenities'])) {
                $business->amenities()->attach($data['amenities']);
            }
            
            // Mark draft as converted
            if ($this->draftId) {
                GuestBusinessDraft::find($this->draftId)->update([
                    'is_converted' => true,
                    'business_id' => $business->id,
                    'converted_at' => now(),
                ]);
            }
            
            DB::commit();
            
            // Store business ID and email in session for login/registration
            session()->put('pending_business_id', $business->id);
            session()->put('pending_business_email', $data['email']);
            session()->forget('guest_draft_id');
            
            // Redirect to login/register page with message
            return redirect()->route('login')->with([
                'success' => 'Business listing created! Please login or create an account to publish it.',
                'info' => 'Your business listing has been saved as a draft. Complete registration to publish it and start receiving customers.'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Failed to create guest business: ' . $e->getMessage());
            
            Notification::make()
                ->title('Error')
                ->body('Failed to create business listing. Please try again.')
                ->danger()
                ->send();
            
            return null;
        }
    }
    
    public function render()
    {
        return view('livewire.create-guest-business');
    }
}
