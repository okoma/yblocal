<?php

namespace App\Filament\Business\Resources;

use App\Filament\Business\Resources\BusinessClaimResource\Pages;
use App\Models\BusinessClaim;
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

class BusinessClaimResource extends Resource
{
    protected static ?string $model = BusinessClaim::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-hand-raised';
    
    protected static ?string $navigationLabel = 'My Claims';
    
    protected static ?string $navigationGroup = 'Business Management';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $modelLabel = 'Business Claim';
    
    protected static ?string $pluralModelLabel = 'Business Claims';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Business to Claim')
                ->description('Select the business you want to claim ownership of')
                ->schema([
                    Forms\Components\Select::make('business_id')
                        ->label('Business')
                        ->options(function () {
                            // Only show unclaimed businesses or businesses not already claimed by this user
                            return Business::where(function ($query) {
                                $query->where('is_claimed', false)
                                    ->orWhere(function ($q) {
                                        $q->where('is_claimed', true)
                                            ->where('user_id', '!=', auth()->id());
                                    });
                            })
                            ->whereDoesntHave('claims', function ($query) {
                                // Exclude businesses with pending/approved claims by current user
                                $query->where('user_id', auth()->id())
                                    ->whereIn('status', ['pending', 'under_review', 'approved']);
                            })
                            ->pluck('business_name', 'id');
                        })
                        ->required()
                        ->searchable()
                        ->native(false)
                        ->helperText('Only unclaimed businesses or businesses you haven\'t already claimed are shown')
                        ->disabled(fn ($context) => $context !== 'create'),
                ])
                ->columns(1),
            
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
                        ->native(false)
                        ->helperText('Your role in the business'),
                    
                    Forms\Components\Textarea::make('claim_message')
                        ->label('Why are you claiming this business?')
                        ->rows(4)
                        ->maxLength(1000)
                        ->required()
                        ->helperText('Explain your relationship with the business and why you should be the owner')
                        ->columnSpanFull(),
                ])
                ->columns(1),
            
            Forms\Components\Section::make('Contact Information')
                ->description('Provide contact details for verification purposes')
                ->schema([
                    Forms\Components\TextInput::make('verification_phone')
                        ->label('Phone Number')
                        ->tel()
                        ->maxLength(20)
                        ->required()
                        ->prefix('+234')
                        ->helperText('We may call to verify your claim'),
                    
                    Forms\Components\TextInput::make('verification_email')
                        ->label('Email Address')
                        ->email()
                        ->maxLength(255)
                        ->required()
                        ->default(auth()->user()->email)
                        ->helperText('We may send a verification email'),
                ])
                ->columns(2),
            
            // Status section (only visible when viewing/editing existing claims)
            Forms\Components\Section::make('Claim Status')
                ->schema([
                    Forms\Components\Placeholder::make('status')
                        ->content(fn (BusinessClaim $record): string => match($record->status) {
                            'pending' => '⏳ Pending Review',
                            'under_review' => '👀 Under Review',
                            'approved' => '✅ Approved',
                            'rejected' => '❌ Rejected',
                            'disputed' => '⚠️ Disputed',
                            default => $record->status,
                        }),
                    
                    Forms\Components\Placeholder::make('reviewed_at')
                        ->label('Reviewed At')
                        ->content(fn (BusinessClaim $record): string => $record->reviewed_at ? $record->reviewed_at->format('M d, Y h:i A') : 'Not reviewed yet')
                        ->visible(fn ($record) => $record && $record->reviewed_at),
                    
                    Forms\Components\Placeholder::make('rejection_reason')
                        ->label('Rejection Reason')
                        ->content(fn (BusinessClaim $record): string => $record->rejection_reason ?? 'N/A')
                        ->visible(fn ($record) => $record && $record->status === 'rejected')
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->visible(fn ($context) => $context !== 'create'),
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
                    ->description(fn ($record) => $record->business->address ?? ''),
                
                Tables\Columns\TextColumn::make('claimant_position')
                    ->label('Position')
                    ->searchable()
                    ->badge()
                    ->color('gray'),
                
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'under_review',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'gray' => 'disputed',
                    ])
                    ->formatStateUsing(fn ($state) => str_replace('_', ' ', ucwords($state, '_')))
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('phone_verified')
                    ->boolean()
                    ->label('Phone ✓')
                    ->toggleable(),
                
                Tables\Columns\IconColumn::make('email_verified')
                    ->boolean()
                    ->label('Email ✓')
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->description(fn ($record) => $record->created_at->format('M d, Y')),
                
                Tables\Columns\TextColumn::make('reviewed_at')
                    ->label('Reviewed')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('Not reviewed'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'under_review' => 'Under Review',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'disputed' => 'Disputed',
                    ])
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (BusinessClaim $record) => in_array($record->status, ['pending', 'rejected'])),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (BusinessClaim $record) => in_array($record->status, ['pending', 'rejected']))
                    ->requiresConfirmation()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Claim Withdrawn')
                            ->body('Your claim has been withdrawn.')
                    ),
            ])
            ->emptyStateHeading('No Claims Yet')
            ->emptyStateDescription('You haven\'t claimed any businesses yet. Click the button below to claim your business.')
            ->emptyStateIcon('heroicon-o-hand-raised')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Claim a Business')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Claim Business Ownership')
                    ->modalDescription('Submit a claim to become the verified owner of a business listing.')
                    ->modalWidth('3xl')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();
                        return $data;
                    })
                    ->before(function (array $data) {
                        // Check for duplicate claims
                        if (BusinessClaim::hasExistingClaim(auth()->id(), $data['business_id'])) {
                            Notification::make()
                                ->danger()
                                ->title('Duplicate Claim')
                                ->body('You already have a pending or approved claim for this business.')
                                ->send();
                            
                            $this->halt();
                        }
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Claim Submitted!')
                            ->body('Your claim has been submitted for review. We will notify you once it has been reviewed.')
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
                        
                        Components\TextEntry::make('business.owner.name')
                            ->label('Current Owner')
                            ->icon('heroicon-m-user')
                            ->visible(fn ($record) => $record->business->owner),
                        
                        Components\IconEntry::make('business.is_claimed')
                            ->boolean()
                            ->label('Already Claimed'),
                        
                        Components\TextEntry::make('business.address')
                            ->label('Address')
                            ->icon('heroicon-m-map-pin')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
                
                Components\Section::make('Your Claim')
                    ->schema([
                        Components\TextEntry::make('claimant_position')
                            ->label('Your Position')
                            ->badge()
                            ->color('primary'),
                        
                        Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn ($state) => match($state) {
                                'pending' => 'warning',
                                'under_review' => 'info',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                'disputed' => 'gray',
                            }),
                        
                        Components\TextEntry::make('claim_message')
                            ->label('Claim Message')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                
                Components\Section::make('Contact Information')
                    ->schema([
                        Components\TextEntry::make('verification_phone')
                            ->label('Phone Number')
                            ->icon('heroicon-m-phone'),
                        
                        Components\IconEntry::make('phone_verified')
                            ->boolean()
                            ->label('Phone Verified'),
                        
                        Components\TextEntry::make('verification_email')
                            ->label('Email Address')
                            ->icon('heroicon-m-envelope'),
                        
                        Components\IconEntry::make('email_verified')
                            ->boolean()
                            ->label('Email Verified'),
                    ])
                    ->columns(2),
                
                Components\Section::make('Review Status')
                    ->schema([
                        Components\TextEntry::make('created_at')
                            ->label('Submitted At')
                            ->dateTime()
                            ->icon('heroicon-m-calendar'),
                        
                        Components\TextEntry::make('reviewed_at')
                            ->label('Reviewed At')
                            ->dateTime()
                            ->icon('heroicon-m-check-circle')
                            ->visible(fn ($record) => $record->reviewed_at),
                        
                        Components\TextEntry::make('approved_at')
                            ->label('Approved At')
                            ->dateTime()
                            ->icon('heroicon-m-check-badge')
                            ->visible(fn ($record) => $record->approved_at),
                        
                        Components\TextEntry::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->visible(fn ($record) => $record->status === 'rejected')
                            ->columnSpanFull()
                            ->color('danger'),
                    ])
                    ->columns(3)
                    ->collapsible(),
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
            'index' => Pages\ListBusinessClaims::route('/'),
            'create' => Pages\CreateBusinessClaim::route('/create'),
            'view' => Pages\ViewBusinessClaim::route('/{record}'),
            'edit' => Pages\EditBusinessClaim::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id())
            ->with(['business', 'business.owner']);
    }

    public static function getNavigationBadge(): ?string
    {
        $pendingCount = static::getModel()::where('user_id', auth()->id())
            ->whereIn('status', ['pending', 'under_review'])
            ->count();
        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function canCreate(): bool
    {
        // Users can always create new claims (duplication is prevented in the form)
        return true;
    }
}
