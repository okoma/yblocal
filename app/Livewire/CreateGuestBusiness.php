<?php
// ============================================
// app/Livewire/CreateGuestBusinessCustom.php
// Custom Form Implementation (No Filament Forms)
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
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;

#[Layout('layouts.business')]
class CreateGuestBusiness extends Component
{
    use WithFileUploads;
    
    // Step tracking
    public int $currentStep = 1;
    public int $totalSteps = 4;
    
    // Step 1: Basic Information
    public $business_name = '';
    public $slug = '';
    public $business_type_id = '';
    public $categories = [];
    public $description = '';
    public $payment_methods = [];
    public $amenities = [];
    public $registration_number = '';
    public $entity_type = '';
    public $years_in_business = 0;
    
    // Step 2: Location & Contact
    public $state_location_id = '';
    public $city_location_id = '';
    public $state = '';
    public $city = '';
    public $area = '';
    public $address = '';
    public $latitude = '';
    public $longitude = '';
    public $phone = '';
    public $email = '';
    public $whatsapp = '';
    public $website = '';
    public $whatsapp_message = '';
    
    // Step 3: Business Hours
    public $monday_open = '';
    public $monday_close = '';
    public $monday_closed = false;
    public $tuesday_open = '';
    public $tuesday_close = '';
    public $tuesday_closed = false;
    public $wednesday_open = '';
    public $wednesday_close = '';
    public $wednesday_closed = false;
    public $thursday_open = '';
    public $thursday_close = '';
    public $thursday_closed = false;
    public $friday_open = '';
    public $friday_close = '';
    public $friday_closed = false;
    public $saturday_open = '';
    public $saturday_close = '';
    public $saturday_closed = true;
    public $sunday_open = '';
    public $sunday_close = '';
    public $sunday_closed = true;
    
    // Step 4: Review & Submit
    public $terms = false;
    
    // Draft tracking
    public ?int $draftId = null;
    public $guestEmail = null;
    
    // Authentication fields
    public bool $has_account = false;
    public $full_name = '';
    public $auth_email = '';
    public $auth_password = '';
    public $verification_code = '';
    public bool $code_sent = false;
    public bool $code_verified = false;
    public ?string $generated_password = null;
    
    // Data for dropdowns
    public $businessTypes = [];
    public $availableCategories = [];
    public $availablePaymentMethods = [];
    public $availableAmenities = [];
    public $states = [];
    public $cities = [];
    
    protected NewBusinessPlanLimits $planLimits;
    
    public function mount()
    {
        $this->planLimits = app(NewBusinessPlanLimits::class);
        
        // Load dropdown data
        $this->businessTypes = BusinessType::where('is_active', true)->orderBy('name')->get();
        $this->availablePaymentMethods = PaymentMethod::where('is_active', true)->orderBy('name')->get();
        $this->availableAmenities = Amenity::where('is_active', true)->orderBy('name')->get();
        $this->states = Location::where('type', 'state')->where('is_active', true)->orderBy('name')->get();
        
        // Check if resuming a draft
        if (session()->has('guest_draft_id')) {
            $this->loadDraft(session('guest_draft_id'));
        }
    }
    
    public function updatedBusinessName($value)
    {
        $this->slug = Str::slug($value);
    }
    
    public function updatedBusinessTypeId($value)
    {
        $this->categories = [];
        if ($value) {
            $this->availableCategories = Category::where('business_type_id', $value)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        } else {
            $this->availableCategories = [];
        }
    }
    
    public function updatedStateLocationId($value)
    {
        $this->city_location_id = '';
        $this->city = '';
        
        if ($value) {
            $this->state = Location::find($value)?->name;
            $this->cities = Location::where('type', 'city')
                ->where('parent_id', $value)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        } else {
            $this->cities = [];
        }
    }
    
    public function updatedCityLocationId($value)
    {
        if ($value) {
            $this->city = Location::find($value)?->name;
        }
    }
    
    public function updatedEmail($value)
    {
        $this->guestEmail = $value;
    }
    
    // Toggle day closed
    public function toggleDay($day)
    {
        $closedProperty = "{$day}_closed";
        $this->{$closedProperty} = !$this->{$closedProperty};
        
        if ($this->{$closedProperty}) {
            $this->{"{$day}_open"} = '';
            $this->{"{$day}_close"} = '';
        }
    }
    
    public function nextStep()
    {
        $this->validateCurrentStep();
        
        if ($this->currentStep < $this->totalSteps) {
            $this->saveDraft($this->currentStep);
            $this->currentStep++;
        }
    }
    
    public function previousStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
            $this->reloadDependentData();
        }
    }
    
    public function goToStep($step)
    {
        if ($step >= 1 && $step <= $this->currentStep) {
            $this->currentStep = $step;
            $this->reloadDependentData();
        }
    }
    
    private function reloadDependentData()
    {
        // Reload categories if on step 1 and business type is selected
        if ($this->currentStep === 1 && $this->business_type_id) {
            $this->updatedBusinessTypeId($this->business_type_id);
            $this->dispatch('reinitialize-choices');
        }
        
        // Reload cities if on step 2 and state is selected
        if ($this->currentStep === 2 && $this->state_location_id) {
            $this->updatedStateLocationId($this->state_location_id);
        }
    }
    
    protected function validateCurrentStep()
    {
        $rules = $this->getValidationRulesForStep($this->currentStep);
        $this->validate($rules);
    }
    
    protected function getValidationRulesForStep($step)
    {
        switch ($step) {
            case 1:
                return [
                    'business_name' => 'required|string|max:255',
                    'slug' => 'required|string|max:255|unique:businesses,slug',
                    'business_type_id' => 'required|exists:business_types,id',
                    'categories' => 'required|array|min:1',
                    'categories.*' => 'exists:categories,id',
                    'description' => 'required|string|max:1000',
                    'years_in_business' => 'required|integer|min:0|max:100',
                ];
            
            case 2:
                return [
                    'state_location_id' => 'required|exists:locations,id',
                    'city_location_id' => 'required|exists:locations,id',
                    'address' => 'required|string|max:255',
                    'phone' => 'required|string|max:20',
                    'email' => 'required|email|max:255',
                ];
            
            case 3:
                $rules = [];
                $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
                foreach ($days as $day) {
                    $rules["{$day}_open"] = "required_if:{$day}_closed,false";
                    $rules["{$day}_close"] = "required_if:{$day}_closed,false";
                }
                return $rules;
            
            case 4:
                return [
                    'terms' => 'accepted',
                ];
            
            default:
                return [];
        }
    }
    
    protected function saveDraft($step)
    {
        try {
            $formData = $this->getFormData();
            
            if ($this->draftId) {
                $draft = GuestBusinessDraft::find($this->draftId);
                if ($draft) {
                    $draft->update([
                        'form_data' => $formData,
                        'current_step' => $step,
                        'guest_email' => $this->guestEmail ?? $this->email,
                        'guest_phone' => $this->phone,
                        'last_activity_at' => now(),
                    ]);
                }
            } else {
                $draft = GuestBusinessDraft::create([
                    'form_data' => $formData,
                    'current_step' => $step,
                    'guest_email' => $this->guestEmail ?? $this->email,
                    'guest_phone' => $this->phone,
                    'session_id' => session()->getId(),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'last_activity_at' => now(),
                ]);
                
                $this->draftId = $draft->id;
                session()->put('guest_draft_id', $draft->id);
            }
            
            session()->flash('draft_saved', 'Your progress has been saved!');
            
        } catch (\Exception $e) {
            \Log::error('Failed to save guest draft: ' . $e->getMessage());
        }
    }
    
    protected function loadDraft($draftId)
    {
        try {
            $draft = GuestBusinessDraft::find($draftId);
            
            if ($draft && !$draft->is_converted) {
                $this->draftId = $draft->id;
                $this->currentStep = $draft->current_step;
                
                // Fill all properties from form_data
                $data = $draft->form_data;
                foreach ($data as $key => $value) {
                    if (property_exists($this, $key)) {
                        $this->{$key} = $value;
                    }
                }
                
                // Load dependent data
                if ($this->business_type_id) {
                    $this->updatedBusinessTypeId($this->business_type_id);
                }
                if ($this->state_location_id) {
                    $this->updatedStateLocationId($this->state_location_id);
                }
                
                $draft->update(['last_activity_at' => now()]);
                
                session()->flash('message', 'Your previous progress has been restored.');
            }
        } catch (\Exception $e) {
            \Log::error('Failed to load guest draft: ' . $e->getMessage());
        }
    }
    
    protected function getFormData()
    {
        return [
            'business_name' => $this->business_name,
            'slug' => $this->slug,
            'business_type_id' => $this->business_type_id,
            'categories' => $this->categories,
            'description' => $this->description,
            'payment_methods' => $this->payment_methods,
            'amenities' => $this->amenities,
            'registration_number' => $this->registration_number,
            'entity_type' => $this->entity_type,
            'years_in_business' => $this->years_in_business,
            'state_location_id' => $this->state_location_id,
            'city_location_id' => $this->city_location_id,
            'state' => $this->state,
            'city' => $this->city,
            'area' => $this->area,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'phone' => $this->phone,
            'email' => $this->email,
            'whatsapp' => $this->whatsapp,
            'website' => $this->website,
            'whatsapp_message' => $this->whatsapp_message,
            'monday_open' => $this->monday_open,
            'monday_close' => $this->monday_close,
            'monday_closed' => $this->monday_closed,
            'tuesday_open' => $this->tuesday_open,
            'tuesday_close' => $this->tuesday_close,
            'tuesday_closed' => $this->tuesday_closed,
            'wednesday_open' => $this->wednesday_open,
            'wednesday_close' => $this->wednesday_close,
            'wednesday_closed' => $this->wednesday_closed,
            'thursday_open' => $this->thursday_open,
            'thursday_close' => $this->thursday_close,
            'thursday_closed' => $this->thursday_closed,
            'friday_open' => $this->friday_open,
            'friday_close' => $this->friday_close,
            'friday_closed' => $this->friday_closed,
            'saturday_open' => $this->saturday_open,
            'saturday_close' => $this->saturday_close,
            'saturday_closed' => $this->saturday_closed,
            'sunday_open' => $this->sunday_open,
            'sunday_close' => $this->sunday_close,
            'sunday_closed' => $this->sunday_closed,
        ];
    }
    
    public function sendVerificationCode()
    {
        $this->validate([
            'auth_email' => 'required|email|max:255',
        ]);

        // Generate 6-digit code
        $code = rand(100000, 999999);
        session()->put("verification_code_{$this->auth_email}", $code);
        session()->put("verification_code_time_{$this->auth_email}", now());

        // Send email with code (using Mail::send or notification)
        try {
            \Mail::send('emails.verification-code', ['code' => $code], function ($message) {
                $message->to($this->auth_email)
                    ->subject('Your Email Verification Code');
            });
            
            $this->code_sent = true;
            session()->flash('message', "Verification code sent to {$this->auth_email}");
        } catch (\Exception $e) {
            \Log::error('Failed to send verification code: ' . $e->getMessage());
            session()->flash('error', 'Failed to send verification code. Please try again.');
        }
    }

    public function verifyCode()
    {
        $this->validate([
            'verification_code' => 'required|numeric|digits:6',
        ]);

        $storedCode = session()->get("verification_code_{$this->auth_email}");
        $codeTime = session()->get("verification_code_time_{$this->auth_email}");

        if (!$storedCode) {
            session()->flash('error', 'No verification code sent. Please try again.');
            return;
        }

        // Check if code expired (15 minutes)
        if (now()->diffInMinutes($codeTime) > 15) {
            session()->forget("verification_code_{$this->auth_email}");
            session()->forget("verification_code_time_{$this->auth_email}");
            session()->flash('error', 'Verification code expired. Please request a new one.');
            return;
        }

        if ($this->verification_code != $storedCode) {
            session()->flash('error', 'Invalid verification code. Please try again.');
            return;
        }

        // Mark as verified
        $this->code_verified = true;
        session()->flash('message', 'Email verified successfully!');
        session()->forget("verification_code_{$this->auth_email}");
        session()->forget("verification_code_time_{$this->auth_email}");
    }

    private function registerUser()
    {
        $this->validate([
            'full_name' => 'required|string|max:255',
            'auth_email' => 'required|email|max:255|unique:users,email',
        ]);

        // Generate a temporary password
        $this->generated_password = Str::random(16);

        $user = \App\Models\User::create([
            'name' => $this->full_name,
            'email' => $this->auth_email,
            'password' => bcrypt($this->generated_password),
            'email_verified_at' => now(), // Verified via OTP
        ]);

        return $user;
    }

    private function loginExistingUser()
    {
        $this->validate([
            'auth_email' => 'required|email|max:255',
            'auth_password' => 'required|string|min:6',
        ]);

        if (!\Illuminate\Support\Facades\Auth::attempt(['email' => $this->auth_email, 'password' => $this->auth_password])) {
            throw new \Exception('Invalid email or password');
        }

        return \Illuminate\Support\Facades\Auth::user();
    }
    
    public function submit()
    {
        $this->validateCurrentStep();

        // Validate auth
        if (!$this->has_account && !$this->code_verified) {
            session()->flash('error', 'Please verify your email before creating the business.');
            return;
        }

        try {
            DB::beginTransaction();

            // Handle authentication
            if (!$this->has_account) {
                // Register new user
                $user = $this->registerUser();
                \Illuminate\Support\Facades\Auth::login($user);
                $userId = $user->id;
            } else {
                // Login existing user
                $user = $this->loginExistingUser();
                $userId = $user->id;
            }

            // Prepare business hours
            $businessHours = [];
            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            
            foreach ($days as $day) {
                $businessHours[$day] = [
                    'open' => $this->{"{$day}_open"} ?? null,
                    'close' => $this->{"{$day}_close"} ?? null,
                    'closed' => $this->{"{$day}_closed"} ?? false,
                ];
            }
            
            // Create the business
            $business = Business::create([
                'business_name' => $this->business_name,
                'slug' => $this->slug,
                'business_type_id' => $this->business_type_id,
                'description' => $this->description,
                'state_location_id' => $this->state_location_id,
                'city_location_id' => $this->city_location_id,
                'state' => $this->state,
                'city' => $this->city,
                'area' => $this->area,
                'address' => $this->address,
                'latitude' => $this->latitude ?: null,
                'longitude' => $this->longitude ?: null,
                'phone' => $this->phone,
                'email' => $this->email,
                'whatsapp' => $this->whatsapp,
                'website' => $this->website,
                'whatsapp_message' => $this->whatsapp_message,
                'registration_number' => $this->registration_number,
                'entity_type' => $this->entity_type,
                'years_in_business' => $this->years_in_business,
                'business_hours' => $businessHours,
                'status' => 'draft',
                'user_id' => $userId,  // Now attach to authenticated user
            ]);
            
            // Attach relationships
            if (!empty($this->categories)) {
                $business->categories()->attach($this->categories);
            }
            
            if (!empty($this->payment_methods)) {
                $business->paymentMethods()->attach($this->payment_methods);
            }
            
            if (!empty($this->amenities)) {
                $business->amenities()->attach($this->amenities);
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
            
            // Clear session
            session()->forget('guest_draft_id');
            session()->forget('pending_business_id');
            
            // If new user, show them their password
            if (!$this->has_account) {
                return redirect()->route('dashboard')->with([
                    'success' => "Business '{$this->business_name}' created successfully!",
                    'password_info' => "Your account has been created. Password: {$this->generated_password}. Please change it in settings.",
                ]);
            } else {
                return redirect()->route('dashboard')->with([
                    'success' => "Business '{$this->business_name}' created and linked to your account!",
                ]);
            }
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Failed to create guest business: ' . $e->getMessage());
            
            session()->flash('error', 'Failed to create business listing: ' . $e->getMessage());
            
            return null;
        }
    }
    
    public function getCompletionPercentage()
    {
        return (int) (($this->currentStep / $this->totalSteps) * 100);
    }
    
    public function render()
    {
        return view('livewire.create-guest-business');
    }
}