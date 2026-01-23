<?php
// ============================================
// app/Filament/Business/Resources/BusinessResource/Pages/ViewBusiness.php
// View business details with inline claim/verification workflow
// ============================================

namespace App\Filament\Business\Resources\BusinessResource\Pages;

use App\Filament\Business\Resources\BusinessResource;
use App\Models\BusinessClaim;
use App\Models\BusinessVerification;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class ViewBusiness extends ViewRecord
{
    protected static string $resource = BusinessResource::class;

    protected function getHeaderActions(): array
    {
        $business = $this->record;
        $actions = [];

        // === CLAIM WORKFLOW ===
        // Check if business is claimed by current user
        $isClaimed = $business->is_claimed && $business->user_id === Auth::id();
        
        // Check if user has a pending/under review claim
        $pendingClaim = BusinessClaim::where('business_id', $business->id)
            ->where('user_id', Auth::id())
            ->whereIn('status', ['pending', 'under_review'])
            ->first();

        if (!$isClaimed && !$pendingClaim) {
            // Show "Claim Business" button
            $actions[] = $this->getClaimBusinessAction();
        } elseif ($pendingClaim) {
            // Show "Reviewing..." badge as a disabled action
            $actions[] = Actions\Action::make('claim_reviewing')
                ->label('Reviewing...')
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->disabled()
                ->badge();
        } elseif ($isClaimed) {
            // Show "Claimed" badge
            $actions[] = Actions\Action::make('claimed_badge')
                ->label('Claimed')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->disabled()
                ->badge();

            // === VERIFICATION WORKFLOW ===
            // Check if business is verified
            if (!$business->is_verified) {
                // Check if user has a pending verification
                $pendingVerification = BusinessVerification::where('business_id', $business->id)
                    ->where('submitted_by', Auth::id())
                    ->whereIn('status', ['pending', 'requires_resubmission'])
                    ->first();

                if (!$pendingVerification) {
                    // Show "Verify Business" button
                    $actions[] = $this->getVerifyBusinessAction();
                } else {
                    // Show "Under Review" badge
                    $actions[] = Actions\Action::make('verification_reviewing')
                        ->label('Under Review')
                        ->icon('heroicon-o-clock')
                        ->color('info')
                        ->disabled()
                        ->badge();
                }
            } else {
                // Show "Verified" badge
                $actions[] = Actions\Action::make('verified_badge')
                    ->label('Verified')
                    ->icon('heroicon-o-shield-check')
                    ->color('success')
                    ->disabled()
                    ->badge();
            }
        }

        // Edit and Delete actions (moved to the end with icons)
        $actions[] = Actions\EditAction::make()
            ->icon('heroicon-o-pencil-square');
        $actions[] = Actions\DeleteAction::make()
            ->icon('heroicon-o-trash');

        return $actions;
    }

    protected function getClaimBusinessAction(): Actions\Action
    {
        return Actions\Action::make('claim_business')
            ->label('Claim Business')
            ->icon('heroicon-o-hand-raised')
            ->color('primary')
            ->modalHeading('Claim Business Ownership')
            ->modalDescription('Submit a claim to become the verified owner of this business.')
            ->modalWidth('3xl')
            ->modalSubmitActionLabel('Claim Business')
            ->modalFooterActionsAlignment('right')
            ->form([
                Forms\Components\Section::make('Claim Information')
                    ->description('Tell us why you are claiming this business')
                    ->schema([
                        Forms\Components\Select::make('claimant_position')
                            ->label('Your Position/Title')
                            ->options([
                                'Owner' => 'Owner',
                                'Co-Owner' => 'Co-Owner',
                                'Manager' => 'Manager',
                                'Director' => 'Director',
                                'CEO' => 'CEO',
                                'Authorized Representative' => 'Authorized Representative',
                            ])
                            ->required()
                            ->native(false),
                        
                        Forms\Components\Textarea::make('claim_message')
                            ->label('Why are you claiming this business?')
                            ->rows(4)
                            ->maxLength(1000)
                            ->required()
                            ->helperText('Explain your relationship with the business')
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
                
                Forms\Components\Section::make('Contact Information')
                    ->description('Provide contact details for verification')
                    ->schema([
                        Forms\Components\TextInput::make('verification_phone')
                            ->label('Phone Number')
                            ->tel()
                            ->maxLength(20)
                            ->required()
                            ->prefix('+234'),
                        
                        Forms\Components\TextInput::make('verification_email')
                            ->label('Email Address')
                            ->email()
                            ->maxLength(255)
                            ->required()
                            ->default(Auth::user()->email),
                    ])
                    ->columns(2),
            ])
            ->action(function (array $data) {
                // Check for duplicate claims
                if (BusinessClaim::hasExistingClaim(Auth::id(), $this->record->id)) {
                    Notification::make()
                        ->danger()
                        ->title('Duplicate Claim')
                        ->body('You already have a pending or approved claim for this business.')
                        ->send();
                    return;
                }

                // Create claim
                BusinessClaim::create([
                    'business_id' => $this->record->id,
                    'user_id' => Auth::id(),
                    'claimant_position' => $data['claimant_position'],
                    'claim_message' => $data['claim_message'],
                    'verification_phone' => $data['verification_phone'],
                    'verification_email' => $data['verification_email'],
                    'status' => 'pending',
                ]);

                Notification::make()
                    ->success()
                    ->title('Claim Submitted!')
                    ->body('Your business claim has been submitted for review. We will notify you once it has been reviewed.')
                    ->persistent()
                    ->send();

                // Refresh the page to show new status
                $this->redirect(static::getUrl(['record' => $this->record]));
            });
    }

    protected function getVerifyBusinessAction(): Actions\Action
    {
        return Actions\Action::make('verify_business')
            ->label('Verify Business')
            ->icon('heroicon-o-shield-check')
            ->color('primary')
            ->modalHeading('Verify Business Ownership')
            ->modalDescription('Submit documents to verify your business and increase trust.')
            ->modalWidth('5xl')
            ->modalSubmitActionLabel('Verify Business')
            ->modalFooterActionsAlignment('right')
            ->form([
                // CAC Verification
                Forms\Components\Section::make('CAC Registration')
                    ->description('Corporate Affairs Commission registration details')
                    ->schema([
                        Forms\Components\TextInput::make('cac_number')
                            ->label('CAC Registration Number')
                            ->maxLength(255)
                            ->required()
                            ->placeholder('RC123456'),
                        
                        Forms\Components\FileUpload::make('cac_document')
                            ->label('CAC Certificate')
                            ->required()
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->maxSize(10240)
                            ->directory('verification-cac')
                            ->helperText('Upload CAC certificate (Max: 10MB)'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                // Location Verification
                Forms\Components\Section::make('Office Location')
                    ->description('Verify your physical business location')
                    ->schema([
                        Forms\Components\Textarea::make('office_address')
                            ->label('Office Address')
                            ->rows(2)
                            ->maxLength(500)
                            ->required()
                            ->columnSpanFull(),
                        
                        Forms\Components\FileUpload::make('office_photo')
                            ->label('Office Photo')
                            ->required()
                            ->image()
                            ->maxSize(5120)
                            ->directory('verification-office')
                            ->helperText('Photo of office/storefront (Max: 5MB)')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                // Email & Website
                Forms\Components\Section::make('Contact Verification')
                    ->schema([
                        Forms\Components\TextInput::make('business_email')
                            ->label('Business Email')
                            ->email()
                            ->maxLength(255)
                            ->required(),
                        
                        Forms\Components\TextInput::make('website_url')
                            ->label('Website (Optional)')
                            ->url()
                            ->maxLength(500)
                            ->prefix('https://'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                // Additional Documents
                Forms\Components\Section::make('Additional Documents (Optional)')
                    ->schema([
                        Forms\Components\FileUpload::make('additional_documents')
                            ->label('Supporting Documents')
                            ->multiple()
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->maxSize(10240)
                            ->maxFiles(10)
                            ->directory('verification-additional')
                            ->helperText('Utility bills, licenses, etc. (Max: 10 files)')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ])
            ->action(function (array $data) {
                // Check if already has pending verification
                $existing = BusinessVerification::where('business_id', $this->record->id)
                    ->whereIn('status', ['pending', 'approved'])
                    ->exists();
                
                if ($existing) {
                    Notification::make()
                        ->warning()
                        ->title('Verification Already Exists')
                        ->body('This business already has a pending or approved verification.')
                        ->send();
                    return;
                }

                // Create verification
                BusinessVerification::create([
                    'business_id' => $this->record->id,
                    'submitted_by' => Auth::id(),
                    'cac_number' => $data['cac_number'],
                    'cac_document' => $data['cac_document'],
                    'office_address' => $data['office_address'],
                    'office_photo' => $data['office_photo'],
                    'business_email' => $data['business_email'],
                    'website_url' => $data['website_url'] ?? null,
                    'additional_documents' => $data['additional_documents'] ?? null,
                    'status' => 'pending',
                    'resubmission_count' => 0,
                ]);

                Notification::make()
                    ->success()
                    ->title('Verification Submitted!')
                    ->body('Your verification request has been submitted. We will review your documents and notify you.')
                    ->persistent()
                    ->send();

                // Refresh the page to show new status
                $this->redirect(static::getUrl(['record' => $this->record]));
            });
    }
    
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Business Overview')
                    ->schema([
                        Components\ImageEntry::make('logo')
                            ->circular()
                            ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->business_name)),
                        
                        Components\TextEntry::make('business_name')
                            ->size('lg')
                            ->weight('bold'),
                        
                        Components\TextEntry::make('businessType.name')
                            ->label('Business Type')
                            ->badge()
                            ->color('info'),
                        
                        Components\TextEntry::make('categories.name')
                            ->badge()
                            ->separator(',')
                            ->color('success'),
                        
                        Components\TextEntry::make('description')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
                
                Components\Section::make('Location & Contact')
                    ->schema([
                        Components\TextEntry::make('address')
                            ->icon('heroicon-o-map-pin'),
                        
                        Components\TextEntry::make('city')
                            ->icon('heroicon-o-building-office'),
                        
                        Components\TextEntry::make('area')
                            ->visible(fn ($state) => !empty($state)),
                        
                        Components\TextEntry::make('state')
                            ->icon('heroicon-o-globe-alt'),
                        
                        Components\TextEntry::make('latitude')
                            ->visible(fn ($state) => !empty($state)),
                        
                        Components\TextEntry::make('longitude')
                            ->visible(fn ($state) => !empty($state)),
                        
                        Components\TextEntry::make('phone')
                            ->icon('heroicon-o-phone')
                            ->copyable(),
                        
                        Components\TextEntry::make('email')
                            ->icon('heroicon-o-envelope')
                            ->copyable(),
                        
                        Components\TextEntry::make('whatsapp')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->copyable(),
                        
                        Components\TextEntry::make('website')
                            ->icon('heroicon-o-globe-alt')
                            ->url(fn ($state) => $state)
                            ->openUrlInNewTab(),
                        
                        Components\TextEntry::make('nearby_landmarks')
                            ->columnSpanFull()
                            ->visible(fn ($state) => !empty($state)),
                    ])
                    ->columns(3),
                
                Components\Section::make('Business Hours')
                    ->schema([
                        Components\ViewEntry::make('business_hours')
                            ->label('')
                            ->view('filament.infolists.business-hours')
                            ->viewData(fn ($record) => [
                                'businessHours' => $record->business_hours ?? [],
                            ])
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => !empty($record->business_hours))
                    ->collapsible(),
                
                Components\Section::make('Business Status')
                    ->schema([
                        Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn ($state) => match($state) {
                                'active' => 'success',
                                'pending_review' => 'warning',
                                'draft' => 'secondary',
                                'suspended' => 'danger',
                                default => 'gray',
                            }),
                        
                        Components\IconEntry::make('is_verified')
                            ->boolean()
                            ->label('Verified'),
                        
                        Components\TextEntry::make('verification_level')
                            ->badge()
                            ->color(fn ($state) => match($state) {
                                'premium' => 'success',
                                'standard' => 'info',
                                'basic' => 'warning',
                                default => 'gray',
                            }),
                        
                        Components\IconEntry::make('is_premium')
                            ->boolean()
                            ->label('Premium'),
                        
                        Components\IconEntry::make('is_claimed')
                            ->boolean()
                            ->label('Claimed'),
                        
                        Components\TextEntry::make('claimed_at')
                            ->dateTime()
                            ->label('Claimed On'),
                    ])
                    ->columns(3),
                
                Components\Section::make('Performance Statistics')
                    ->schema([
                        Components\TextEntry::make('avg_rating')
                            ->label('Average Rating')
                            ->formatStateUsing(fn ($state) => number_format($state, 1) . ' ⭐')
                            ->size('lg')
                            ->weight('bold'),
                        
                        Components\TextEntry::make('total_reviews')
                            ->label('Total Reviews')
                            ->badge()
                            ->color('info'),
                        
                        Components\TextEntry::make('total_views')
                            ->label('Total Views')
                            ->badge()
                            ->color('success'),
                        
                        Components\TextEntry::make('total_leads')
                            ->label('Total Leads')
                            ->badge()
                            ->color('warning'),
                        
                        Components\TextEntry::make('total_saves')
                            ->label('Total Saves')
                            ->badge()
                            ->color('primary'),
                        
                    ])
                    ->columns(3),
                
                Components\Section::make('Features & Amenities')
                    ->schema([
                        Components\TextEntry::make('unique_features')
                            ->badge()
                            ->formatStateUsing(function ($state) {
                                if (empty($state)) {
                                    return null;
                                }
                                return is_array($state) ? $state : null;
                            })
                            ->separator(',')
                            ->visible(fn ($state) => !empty($state)),
                        
                        Components\TextEntry::make('amenities.name')
                            ->label('Amenities')
                            ->badge()
                            ->separator(',')
                            ->color('success')
                            ->visible(fn ($record) => $record->amenities()->exists()),
                        
                        Components\TextEntry::make('paymentMethods.name')
                            ->label('Payment Methods')
                            ->badge()
                            ->separator(',')
                            ->color('info')
                            ->visible(fn ($record) => $record->paymentMethods()->exists()),
                    ])
                    ->columns(1)
                    ->visible(fn ($record) => 
                        !empty($record->unique_features) || 
                        $record->amenities()->exists() || 
                        $record->paymentMethods()->exists()
                    )
                    ->collapsible()
                    ->collapsed(),
                
                Components\Section::make('SEO Settings')
                    ->schema([
                        Components\TextEntry::make('canonical_strategy')
                            ->badge()
                            ->color(fn ($state) => $state === 'self' ? 'success' : 'info'),
                        
                        Components\TextEntry::make('canonical_url')
                            ->visible(fn ($state) => !empty($state))
                            ->url(fn ($state) => $state)
                            ->openUrlInNewTab(),
                        
                        Components\TextEntry::make('meta_title')
                            ->visible(fn ($state) => !empty($state)),
                        
                        Components\TextEntry::make('meta_description')
                            ->visible(fn ($state) => !empty($state))
                            ->columnSpanFull(),
                        
                        Components\IconEntry::make('has_unique_content')
                            ->boolean()
                            ->label('Has Unique Content'),
                        
                        Components\TextEntry::make('content_similarity_score')
                            ->suffix('%')
                            ->visible(fn ($state) => !empty($state)),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),
                
                Components\Section::make('Legal Information')
                    ->schema([
                        Components\TextEntry::make('registration_number')
                            ->label('CAC/RC Number'),
                        
                        Components\TextEntry::make('entity_type'),
                        
                        Components\TextEntry::make('years_in_business')
                            ->suffix(' years'),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),
                
                Components\Section::make('Media')
                    ->schema([
                        Components\ImageEntry::make('cover_photo')
                            ->columnSpanFull()
                            ->visible(fn ($state) => !empty($state)),
                        
                        Components\ImageEntry::make('gallery')
                            ->columnSpanFull()
                            ->limit(10)
                            ->visible(fn ($state) => !empty($state)),
                    ])
                    ->visible(fn ($record) => !empty($record->cover_photo) || !empty($record->gallery))
                    ->collapsible()
                    ->collapsed(),
                
                Components\Section::make('System Information')
                    ->schema([
                        Components\TextEntry::make('created_at')
                            ->dateTime()
                            ->label('Created'),
                        
                        Components\TextEntry::make('updated_at')
                            ->dateTime()
                            ->label('Last Updated'),
                        
                        Components\TextEntry::make('slug')
                            ->copyable(),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
