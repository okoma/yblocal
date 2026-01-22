<?php

namespace App\Filament\Business\Resources;

use App\Filament\Business\Resources\BusinessVerificationResource\Pages;
use App\Models\BusinessVerification;
use App\Models\Business;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class BusinessVerificationResource extends Resource
{
    protected static ?string $model = BusinessVerification::class;
    
    protected static ?string$navigationIcon = 'heroicon-o-shield-check';
    
    protected static ?string $navigationLabel = 'Verifications';
    
    protected static ?string $navigationGroup = 'Business Management';
    
    protected static ?int $navigationSort = 2;
    
    protected static ?string $modelLabel = 'Verification';
    
    protected static ?string $pluralModelLabel = 'Verifications';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Wizard::make([
                // STEP 1: Select Business
                Forms\Components\Wizard\Step::make('Select Business')
                    ->icon('heroicon-o-building-storefront')
                    ->schema([
                        Forms\Components\Section::make()
                            ->description('Select one of your claimed businesses to verify')
                            ->schema([
                                Forms\Components\Select::make('business_id')
                                    ->label('Business')
                                    ->options(function () {
                                        // Only show businesses claimed by the current user
                                        return Business::where('user_id', auth()->id())
                                            ->where('is_claimed', true)
                                            ->pluck('business_name', 'id');
                                    })
                                    ->required()
                                    ->searchable()
                                    ->native(false)
                                    ->helperText('Only your claimed businesses are shown. You must claim a business before verifying it.')
                                    ->disabled(fn ($context) => $context !== 'create'),
                            ]),
                    ]),

                // STEP 2: CAC Verification
                Forms\Components\Wizard\Step::make('CAC Registration')
                    ->icon('heroicon-o-document-text')
                    ->description('Provide your Corporate Affairs Commission registration details')
                    ->schema([
                        Forms\Components\TextInput::make('cac_number')
                            ->label('CAC Registration Number')
                            ->maxLength(255)
                            ->required()
                            ->helperText('Your company RC number')
                            ->placeholder('RC123456'),
                        
                        Forms\Components\FileUpload::make('cac_document')
                            ->label('CAC Certificate')
                            ->required()
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->maxSize(10240)
                            ->directory('verification-cac')
                            ->downloadable()
                            ->openable()
                            ->helperText('Upload your CAC certificate or incorporation document (Max: 10MB)'),
                    ]),

                // STEP 3: Location Verification
                Forms\Components\Wizard\Step::make('Office Location')
                    ->icon('heroicon-o-map-pin')
                    ->description('Verify your physical business location')
                    ->schema([
                        Forms\Components\Textarea::make('office_address')
                            ->label('Office Address')
                            ->rows(2)
                            ->maxLength(500)
                            ->required()
                            ->helperText('Full physical office/storefront address')
                            ->columnSpanFull(),
                        
                        Forms\Components\FileUpload::make('office_photo')
                            ->label('Office Photo')
                            ->required()
                            ->image()
                            ->maxSize(5120)
                            ->directory('verification-office')
                            ->imageEditor()
                            ->downloadable()
                            ->openable()
                            ->helperText('Photo showing your office building or storefront (Max: 5MB)')
                            ->columnSpanFull(),
                        
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('office_latitude')
                                    ->label('Latitude (Optional)')
                                    ->numeric()
                                    ->step(0.0000001)
                                    ->helperText('GPS latitude coordinate'),
                                
                                Forms\Components\TextInput::make('office_longitude')
                                    ->label('Longitude (Optional)')
                                    ->numeric()
                                    ->step(0.0000001)
                                    ->helperText('GPS longitude coordinate'),
                            ]),
                    ]),

                // STEP 4: Business Email
                Forms\Components\Wizard\Step::make('Business Email')
                    ->icon('heroicon-o-envelope')
                    ->description('Verify your official business email')
                    ->schema([
                        Forms\Components\TextInput::make('business_email')
                            ->label('Business Email Address')
                            ->email()
                            ->maxLength(255)
                            ->required()
                            ->helperText('Your official business email (preferably company domain, not Gmail/Yahoo)')
                            ->placeholder('contact@yourbusiness.com'),
                    ]),

                // STEP 5: Website (Optional)
                Forms\Components\Wizard\Step::make('Website')
                    ->icon('heroicon-o-globe-alt')
                    ->description('Verify your business website (optional)')
                    ->schema([
                        Forms\Components\TextInput::make('website_url')
                            ->label('Website URL')
                            ->url()
                            ->maxLength(500)
                            ->prefix('https://')
                            ->helperText('Your business website (optional but increases verification score)'),
                    ]),

                // STEP 6: Additional Documents
                Forms\Components\Wizard\Step::make('Additional Documents')
                    ->icon('heroicon-o-paper-clip')
                    ->description('Upload supporting documents (optional)')
                    ->schema([
                        Forms\Components\FileUpload::make('additional_documents')
                            ->label('Supporting Documents')
                            ->multiple()
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->maxSize(10240)
                            ->maxFiles(10)
                            ->directory('verification-additional')
                            ->downloadable()
                            ->openable()
                            ->helperText('Utility bills, tax certificates, business licenses, etc. (Optional, Max: 10 files, 10MB each)')
                            ->columnSpanFull(),
                    ]),
            ])
            ->columnSpanFull()
            ->skippable()
            ->persistStepInQueryString(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('business.business_name')
                    ->label('Business')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => "Score: {$record->verification_score}/100"),
                
                Tables\Columns\IconColumn::make('cac_verified')
                    ->boolean()
                    ->label('CAC')
                    ->tooltip('CAC Verified (40 points)'),
                
                Tables\Columns\IconColumn::make('location_verified')
                    ->boolean()
                    ->label('Location')
                    ->tooltip('Location Verified (30 points)'),
                
                Tables\Columns\IconColumn::make('email_verified')
                    ->boolean()
                    ->label('Email')
                    ->tooltip('Email Verified (20 points)'),
                
                Tables\Columns\IconColumn::make('website_verified')
                    ->boolean()
                    ->label('Website')
                    ->tooltip('Website Verified (10 points)'),
                
                Tables\Columns\TextColumn::make('verification_score')
                    ->label('Score')
                    ->sortable()
                    ->suffix('/100')
                    ->badge()
                    ->color(fn ($state) => match(true) {
                        $state >= 90 => 'success',
                        $state >= 70 => 'info',
                        $state >= 40 => 'warning',
                        default => 'danger',
                    }),
                
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'info' => 'requires_resubmission',
                    ])
                    ->formatStateUsing(fn ($state) => str_replace('_', ' ', ucwords($state, '_')))
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->description(fn ($record) => $record->created_at->format('M d, Y')),
                
                Tables\Columns\TextColumn::make('resubmission_count')
                    ->label('Resubmissions')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'requires_resubmission' => 'Requires Resubmission',
                    ])
                    ->multiple(),
                
                Tables\Filters\SelectFilter::make('business_id')
                    ->label('Business')
                    ->relationship('business', 'business_name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (BusinessVerification $record) => in_array($record->status, ['pending', 'requires_resubmission'])),
                Tables\Actions\Action::make('resubmit')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->action(function (BusinessVerification $record) {
                        $record->resubmit();
                        
                        Notification::make()
                            ->success()
                            ->title('Resubmitted')
                            ->body('Your verification has been resubmitted for review.')
                            ->send();
                    })
                    ->visible(fn (BusinessVerification $record) => $record->status === 'requires_resubmission'),
            ])
            ->emptyStateHeading('No Verifications Yet')
            ->emptyStateDescription('You haven\'t submitted any verification requests yet. Start by verifying one of your claimed businesses.')
            ->emptyStateIcon('heroicon-o-shield-check')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Verify a Business')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Business Verification')
                    ->modalDescription('Submit documents to verify your business ownership and increase trust.')
                    ->modalWidth('5xl')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['submitted_by'] = auth()->id();
                        $data['status'] = 'pending';
                        return $data;
                    })
                    ->before(function (array $data) {
                        // Check if business is claimed by current user
                        $business = Business::find($data['business_id']);
                        if (!$business || $business->user_id !== auth()->id()) {
                            Notification::make()
                                ->danger()
                                ->title('Not Authorized')
                                ->body('You can only verify businesses that you own.')
                                ->send();
                            
                            $this->halt();
                        }
                        
                        // Check for existing pending verification
                        $existing = BusinessVerification::where('business_id', $data['business_id'])
                            ->whereIn('status', ['pending', 'approved'])
                            ->exists();
                        
                        if ($existing) {
                            Notification::make()
                                ->warning()
                                ->title('Verification Already Exists')
                                ->body('This business already has a pending or approved verification.')
                                ->send();
                            
                            $this->halt();
                        }
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Verification Submitted!')
                            ->body('Your verification request has been submitted. We will review your documents and notify you of the result.')
                            ->persistent()
                    ),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Business Information')
                    ->schema([
                        Components\TextEntry::make('business.business_name')
                            ->label('Business Name')
                            ->icon('heroicon-m-building-storefront'),
                        
                        Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn ($state) => match($state) {
                                'pending' => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                'requires_resubmission' => 'info',
                            }),
                        
                        Components\TextEntry::make('verification_score')
                            ->label('Verification Score')
                            ->suffix(' / 100')
                            ->badge()
                            ->color(fn ($state) => match(true) {
                                $state >= 90 => 'success',
                                $state >= 70 => 'info',
                                $state >= 40 => 'warning',
                                default => 'danger',
                            }),
                    ])
                    ->columns(3),
                
                Components\Section::make('CAC Verification')
                    ->schema([
                        Components\TextEntry::make('cac_number')
                            ->label('CAC Number'),
                        
                        Components\IconEntry::make('cac_verified')
                            ->boolean()
                            ->label('Verified'),
                        
                        Components\TextEntry::make('cac_document')
                            ->label('Document')
                            ->formatStateUsing(fn ($state) => $state ? 'Uploaded' : 'Not uploaded')
                            ->url(fn ($record) => $record->cac_document ? asset('storage/' . $record->cac_document) : null)
                            ->openUrlInNewTab(),
                        
                        Components\TextEntry::make('cac_notes')
                            ->label('Admin Notes')
                            ->visible(fn ($record) => $record->cac_notes)
                            ->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->collapsible(),
                
                Components\Section::make('Location Verification')
                    ->schema([
                        Components\TextEntry::make('office_address')
                            ->label('Address')
                            ->columnSpanFull(),
                        
                        Components\IconEntry::make('location_verified')
                            ->boolean()
                            ->label('Verified'),
                        
                        Components\ImageEntry::make('office_photo')
                            ->label('Office Photo')
                            ->visible(fn ($record) => $record->office_photo),
                        
                        Components\TextEntry::make('location_notes')
                            ->label('Admin Notes')
                            ->visible(fn ($record) => $record->location_notes)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),
                
                Components\Section::make('Email & Website Verification')
                    ->schema([
                        Components\TextEntry::make('business_email')
                            ->label('Business Email')
                            ->icon('heroicon-m-envelope'),
                        
                        Components\IconEntry::make('email_verified')
                            ->boolean()
                            ->label('Email Verified'),
                        
                        Components\TextEntry::make('website_url')
                            ->label('Website')
                            ->icon('heroicon-m-globe-alt')
                            ->url(fn ($record) => $record->website_url)
                            ->openUrlInNewTab()
                            ->visible(fn ($record) => $record->website_url),
                        
                        Components\IconEntry::make('website_verified')
                            ->boolean()
                            ->label('Website Verified')
                            ->visible(fn ($record) => $record->website_url),
                    ])
                    ->columns(2)
                    ->collapsible(),
                
                Components\Section::make('Review Feedback')
                    ->schema([
                        Components\TextEntry::make('admin_feedback')
                            ->label('Feedback from Admin')
                            ->visible(fn ($record) => $record->admin_feedback)
                            ->columnSpanFull(),
                        
                        Components\TextEntry::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->visible(fn ($record) => $record->status === 'rejected')
                            ->color('danger')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record->admin_feedback || $record->rejection_reason)
                    ->collapsible(),
                
                Components\Section::make('Timestamps')
                    ->schema([
                        Components\TextEntry::make('created_at')
                            ->dateTime()
                            ->label('Submitted At'),
                        
                        Components\TextEntry::make('verified_at')
                            ->dateTime()
                            ->label('Verified At')
                            ->visible(fn ($record) => $record->verified_at),
                        
                        Components\TextEntry::make('resubmission_count')
                            ->label('Resubmissions'),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBusinessVerifications::route('/'),
            'create' => Pages\CreateBusinessVerification::route('/create'),
            'view' => Pages\ViewBusinessVerification::route('/{record}'),
            'edit' => Pages\EditBusinessVerification::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('business', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->with(['business']);
    }

    public static function getNavigationBadge(): ?string
    {
        $pendingCount = static::getModel()::whereHas('business', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->whereIn('status', ['requires_resubmission'])
            ->count();
        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function canCreate(): bool
    {
        // Check if user has any claimed businesses
        return Business::where('user_id', auth()->id())
            ->where('is_claimed', true)
            ->exists();
    }
}
