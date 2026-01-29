<?php
// ============================================
// app/Filament/Business/Resources/AdPackageResource.php
// Browse and purchase advertising packages
// ============================================

namespace App\Filament\Business\Resources;

use App\Filament\Business\Resources\AdPackageResource\Pages;
use App\Models\AdPackage;
use App\Models\Business;
use App\Models\Category;
use App\Models\Location;
use App\Services\ActiveBusiness;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class AdPackageResource extends Resource
{
    protected static ?string $model = AdPackage::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'Ad Packages';

    protected static ?string $navigationGroup = 'Billing & Marketing';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        // Only show active packages to business users
        return parent::getEloquentQuery()
            ->where('is_active', true)
            ->orderBy('order')
            ->orderBy('price');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Package Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->disabled()
                            ->label('Package Name'),

                        Forms\Components\Textarea::make('description')
                            ->disabled()
                            ->rows(3),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('price')
                                    ->disabled()
                                    ->prefix('₦')
                                    ->numeric(),

                                Forms\Components\TextInput::make('duration_days')
                                    ->disabled()
                                    ->suffix('days')
                                    ->label('Duration'),

                                Forms\Components\TextInput::make('campaign_type')
                                    ->disabled()
                                    ->label('Campaign Type')
                                    ->formatStateUsing(fn ($state) => ucwords(str_replace('_', ' ', $state))),
                            ]),
                    ]),

                Forms\Components\Section::make('Features')
                    ->schema([
                        Forms\Components\Placeholder::make('features_list')
                            ->label('')
                            ->content(function ($record) {
                                if (!$record || !$record->features) {
                                    return 'No features listed';
                                }

                                return view('filament.forms.components.features-list', [
                                    'features' => $record->features
                                ]);
                            }),
                    ]),

                Forms\Components\Section::make('Limits')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('impressions_limit')
                                    ->disabled()
                                    ->label('Max Impressions')
                                    ->formatStateUsing(fn ($state) => $state ? number_format($state) : 'Unlimited'),

                                Forms\Components\TextInput::make('clicks_limit')
                                    ->disabled()
                                    ->label('Max Clicks')
                                    ->formatStateUsing(fn ($state) => $state ? number_format($state) : 'Unlimited'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->size('lg'),

                Tables\Columns\TextColumn::make('campaign_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'bump_up' => 'info',
                        'sponsored' => 'warning',
                        'featured' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state)))
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->money('NGN')
                    ->sortable()
                    ->size('lg')
                    ->weight('bold')
                    ->color('success'),

                Tables\Columns\TextColumn::make('duration_days')
                    ->label('Duration')
                    ->suffix(' days')
                    ->sortable(),

                Tables\Columns\TextColumn::make('impressions_limit')
                    ->label('Max Impressions')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state) : 'Unlimited')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('clicks_limit')
                    ->label('Max Clicks')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state) : 'Unlimited')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_popular')
                    ->label('Popular')
                    ->boolean()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('campaign_type')
                    ->label('Campaign Type')
                    ->options([
                        'bump_up' => 'Bump Up',
                        'sponsored' => 'Sponsored',
                        'featured' => 'Featured',
                    ]),

                Tables\Filters\TernaryFilter::make('is_popular')
                    ->label('Show Popular Only')
                    ->placeholder('All packages')
                    ->trueLabel('Popular only')
                    ->falseLabel('Non-popular only'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('purchase')
                    ->label('Purchase')
                    ->icon('heroicon-o-shopping-cart')
                    ->color('success')
                    ->modalWidth('3xl')
                    ->form([
                        Forms\Components\Select::make('business_id')
                            ->label('Business')
                            ->options(function () {
                                $active = app(ActiveBusiness::class);
                                $b = $active->getActiveBusiness();
                                return $b ? [$b->id => $b->business_name] : [];
                            })
                            ->default(fn () => app(ActiveBusiness::class)->getActiveBusinessId())
                            ->required()
                            ->hidden(),

                        Forms\Components\DatePicker::make('start_date')
                            ->label('Start Date')
                            ->default(now())
                            ->minDate(now())
                            ->required()
                            ->helperText('When should the campaign begin?'),

                        Forms\Components\Section::make('Targeting (Optional)')
                            ->description('Leave empty to target all categories and locations')
                            ->schema([
                                Forms\Components\Select::make('target_categories')
                                    ->label('Target Categories')
                                    ->multiple()
                                    ->options(function () {
                                        return Category::where('is_active', true)
                                            ->orderBy('name')
                                            ->pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Select categories to target (leave empty for all categories)'),

                                Forms\Components\Select::make('target_locations')
                                    ->label('Target Locations')
                                    ->multiple()
                                    ->options(function () {
                                        return Location::where('is_active', true)
                                            ->whereIn('type', ['state', 'city'])
                                            ->orderBy('name')
                                            ->pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Select locations to target (leave empty for all locations)'),
                            ])
                            ->columns(2)
                            ->collapsible()
                            ->collapsed(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Campaign Notes (Optional)')
                            ->rows(2)
                            ->maxLength(500),

                        Forms\Components\Section::make('Package Details')
                            ->schema([
                                Forms\Components\Placeholder::make('duration')
                                    ->label('Duration')
                                    ->content(fn ($record) => $record->duration_days . ' day' . ($record->duration_days != 1 ? 's' : '')),
                                
                                Forms\Components\Placeholder::make('max_impressions')
                                    ->label('Max Impressions')
                                    ->content(fn ($record) => $record->impressions_limit ? number_format($record->impressions_limit) : 'Unlimited'),
                                
                                Forms\Components\Placeholder::make('max_clicks')
                                    ->label('Max Clicks')
                                    ->content(fn ($record) => $record->clicks_limit ? number_format($record->clicks_limit) : 'Unlimited'),
                            ])
                            ->columns(3),

                        Forms\Components\Section::make('Package Summary')
                            ->schema([
                                Forms\Components\Placeholder::make('package_price')
                                    ->label('Package Price')
                                    ->content(fn ($record) => '₦' . number_format($record->price, 2))
                                    ->extraAttributes(['class' => 'text-lg font-bold']),
                                
                                Forms\Components\Placeholder::make('credits_cost')
                                    ->label('Credits Required')
                                    ->content(fn ($record) => number_format($record->getCreditsCost()) . ' credits')
                                    ->extraAttributes(['class' => 'text-lg font-semibold text-primary-600']),
                                
                                Forms\Components\Placeholder::make('available_credits')
                                    ->label('Your Available Credits')
                                    ->content(function ($record, Forms\Get $get) {
                                        $businessId = $get('business_id');
                                        if (!$businessId) {
                                            return '0 credits';
                                        }
                                        $wallet = \App\Models\Wallet::where('business_id', $businessId)->first();
                                        $credits = $wallet ? $wallet->ad_credits : 0;
                                        $required = $record->getCreditsCost();
                                        $color = $credits >= $required ? 'text-success-600' : 'text-danger-600';
                                        return new HtmlString('<span class="' . $color . ' font-bold">' . number_format($credits) . ' credits</span>');
                                    })
                                    ->extraAttributes(['class' => 'text-base'])
                                    ->visible(fn (Forms\Get $get) => $get('business_id') !== null),
                                
                                Forms\Components\Placeholder::make('insufficient_credits_warning')
                                    ->label('')
                                    ->content(function ($record, Forms\Get $get) {
                                        $businessId = $get('business_id');
                                        if (!$businessId) {
                                            return new HtmlString('<p class="text-sm text-gray-500">Please select a business first.</p>');
                                        }
                                        $wallet = \App\Models\Wallet::where('business_id', $businessId)->first();
                                        $credits = $wallet ? $wallet->ad_credits : 0;
                                        $required = $record->getCreditsCost();
                                        
                                        if ($credits < $required) {
                                            $shortfall = $required - $credits;
                                            return new HtmlString('<p class="text-sm text-danger-600 font-medium">⚠️ Insufficient credits. You need ' . number_format($shortfall) . ' more credits. <a href="' . route('filament.business.pages.wallet-page') . '" class="underline" target="_blank">Purchase credits</a> to continue.</p>');
                                        }
                                        return new HtmlString('<p class="text-sm text-success-600">✓ You have sufficient credits to purchase this package.</p>');
                                    })
                                    ->visible(fn (Forms\Get $get) => $get('business_id') !== null),
                            ])
                            ->columns(1),
                    ])
                    ->action(function (AdPackage $record, array $data) {
                        try {
                            // Validate business ownership
                            $business = auth()->user()->businesses()->find($data['business_id']);
                            if (!$business) {
                                Notification::make()
                                    ->danger()
                                    ->title('Invalid Business')
                                    ->body('The selected business was not found or you do not have access to it.')
                                    ->send();
                                return;
                            }
                            
                            // Get wallet and check credits
                            $wallet = \App\Models\Wallet::where('business_id', $data['business_id'])->first();
                            if (!$wallet) {
                                Notification::make()
                                    ->danger()
                                    ->title('Wallet Not Found')
                                    ->body('Please contact support to set up your wallet.')
                                    ->send();
                                return;
                            }
                            
                            $creditsRequired = $record->getCreditsCost();
                            if ($wallet->ad_credits < $creditsRequired) {
                                Notification::make()
                                    ->danger()
                                    ->title('Insufficient Credits')
                                    ->body("You need {$creditsRequired} credits but only have {$wallet->ad_credits} credits. Please purchase more credits first.")
                                    ->actions([
                                        \Filament\Notifications\Actions\Action::make('purchase_credits')
                                            ->label('Purchase Credits')
                                            ->url(route('filament.business.pages.wallet-page'))
                                            ->button(),
                                    ])
                                    ->send();
                                return;
                            }
                            
                            // Use database transaction
                            \Illuminate\Support\Facades\DB::beginTransaction();
                            
                            try {
                                // Prepare custom data
                                $customData = [
                                    'starts_at' => $data['start_date'],
                                    'ends_at' => now()->parse($data['start_date'])->addDays($record->duration_days),
                                    'description' => $data['notes'] ?? null,
                                    'is_paid' => true,
                                    'is_active' => true, // Activate immediately after credit deduction
                                ];

                                // Add target categories if selected
                                if (!empty($data['target_categories'])) {
                                    $customData['target_categories'] = $data['target_categories'];
                                }

                                // Add target locations if selected
                                if (!empty($data['target_locations'])) {
                                    $customData['target_locations'] = $data['target_locations'];
                                }

                                // Create the campaign
                                $campaign = $record->createCampaign(
                                    $data['business_id'],
                                    auth()->id(),
                                    $customData
                                );

                                // Deduct credits from wallet
                                $wallet->useCredits(
                                    $creditsRequired,
                                    "Ad package purchase: {$record->name} ({$record->campaign_type})",
                                    $campaign
                                );

                                \Illuminate\Support\Facades\DB::commit();

                                Notification::make()
                                    ->success()
                                    ->title('Campaign Created Successfully!')
                                    ->body("Your campaign has been created and activated. {$creditsRequired} credits have been deducted from your wallet.")
                                    ->actions([
                                        \Filament\Notifications\Actions\Action::make('view')
                                            ->label('View Campaign')
                                            ->url(fn () => \App\Filament\Business\Resources\AdCampaignResource::getUrl('view', ['record' => $campaign])),
                                    ])
                                    ->send();

                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\DB::rollBack();
                                throw $e;
                            }

                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error('Ad package purchase failed', [
                                'package_id' => $record->id,
                                'user_id' => auth()->id(),
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString(),
                            ]);
                            
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body('Failed to create campaign: ' . $e->getMessage())
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Purchase Ad Package')
                    ->modalDescription(function ($record) {
                        $businessId = app(\App\Services\ActiveBusiness::class)->getActiveBusinessId();
                        $wallet = $businessId ? \App\Models\Wallet::where('business_id', $businessId)->first() : null;
                        $credits = $wallet ? $wallet->ad_credits : 0;
                        $required = $record->getCreditsCost();
                        
                        $desc = 'You are about to purchase the "' . $record->name . '" package for ' . number_format($required) . ' credits.';
                        
                        if ($credits < $required) {
                            $desc .= "\n\n⚠️ Warning: You have insufficient credits (" . number_format($credits) . " available, " . number_format($required) . " required).";
                        }
                        
                        return $desc;
                    })
                    ->modalSubmitActionLabel('Purchase with Credits')
                    ->modalFooterActionsAlignment('right'),
            ])
            ->bulkActions([
                // No bulk actions needed for business users
            ])
            ->emptyStateHeading('No Ad Packages Available')
            ->emptyStateDescription('There are currently no advertising packages available. Please check back later.')
            ->emptyStateIcon('heroicon-o-shopping-bag')
            ->defaultSort('order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdPackages::route('/'),
            'view' => Pages\ViewAdPackage::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Business users cannot create packages
    }

    public static function canEdit($record): bool
    {
        return false; // Business users cannot edit packages
    }

    public static function canDelete($record): bool
    {
        return false; // Business users cannot delete packages
    }
}