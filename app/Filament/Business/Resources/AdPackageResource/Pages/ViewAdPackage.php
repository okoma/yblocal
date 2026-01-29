<?php
// ============================================
// app/Filament/Business/Resources/AdPackageResource/Pages/ViewAdPackage.php
// ============================================

namespace App\Filament\Business\Resources\AdPackageResource\Pages;

use App\Filament\Business\Resources\AdPackageResource;
use App\Models\Business;
use App\Models\Wallet;
use App\Services\ActiveBusiness;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;

class ViewAdPackage extends ViewRecord
{
    protected static string $resource = AdPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('purchase')
                ->label('Purchase This Package')
                ->icon('heroicon-o-shopping-cart')
                ->color('success')
                ->size('lg')
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

                    Forms\Components\Textarea::make('notes')
                        ->label('Campaign Notes (Optional)')
                        ->rows(2)
                        ->maxLength(500),
                    
                    Forms\Components\Section::make('Package Details')
                        ->schema([
                            Forms\Components\Placeholder::make('duration')
                                ->label('Duration')
                                ->content(fn () => $this->getRecord()->duration_days . ' day' . ($this->getRecord()->duration_days != 1 ? 's' : '')),
                            
                            Forms\Components\Placeholder::make('max_impressions')
                                ->label('Max Impressions')
                                ->content(fn () => $this->getRecord()->impressions_limit ? number_format($this->getRecord()->impressions_limit) : 'Unlimited'),
                            
                            Forms\Components\Placeholder::make('max_clicks')
                                ->label('Max Clicks')
                                ->content(fn () => $this->getRecord()->clicks_limit ? number_format($this->getRecord()->clicks_limit) : 'Unlimited'),
                        ])
                        ->columns(3),
                    
                    Forms\Components\Section::make('Package Summary')
                        ->schema([
                            Forms\Components\Placeholder::make('package_price')
                                ->label('Package Price')
                                ->content(fn () => '₦' . number_format($this->getRecord()->price, 2))
                                ->extraAttributes(['class' => 'text-lg font-bold']),
                            
                            Forms\Components\Placeholder::make('credits_cost')
                                ->label('Credits Required')
                                ->content(fn () => number_format($this->getRecord()->getCreditsCost()) . ' credits')
                                ->extraAttributes(['class' => 'text-lg font-semibold text-primary-600']),
                            
                            Forms\Components\Placeholder::make('available_credits')
                                ->label('Your Available Credits')
                                ->content(function () {
                                    $businessId = app(ActiveBusiness::class)->getActiveBusinessId();
                                    if (!$businessId) {
                                        return '0 credits';
                                    }
                                    $wallet = Wallet::where('business_id', $businessId)->first();
                                    $credits = $wallet ? $wallet->ad_credits : 0;
                                    $required = $this->getRecord()->getCreditsCost();
                                    $color = $credits >= $required ? 'text-success-600' : 'text-danger-600';
                                    return new HtmlString('<span class="' . $color . ' font-bold">' . number_format($credits) . ' credits</span>');
                                })
                                ->extraAttributes(['class' => 'text-base']),
                            
                            Forms\Components\Placeholder::make('insufficient_credits_warning')
                                ->label('')
                                ->content(function () {
                                    $businessId = app(ActiveBusiness::class)->getActiveBusinessId();
                                    if (!$businessId) {
                                        return new HtmlString('<p class="text-sm text-danger-600">Please select a business first.</p>');
                                    }
                                    $wallet = Wallet::where('business_id', $businessId)->first();
                                    $credits = $wallet ? $wallet->ad_credits : 0;
                                    $required = $this->getRecord()->getCreditsCost();
                                    
                                    if ($credits < $required) {
                                        $shortfall = $required - $credits;
                                        return new HtmlString('<p class="text-sm text-danger-600 font-medium">⚠️ Insufficient credits. You need ' . number_format($shortfall) . ' more credits. <a href="' . route('filament.business.pages.wallet-page') . '" class="underline">Purchase credits</a> to continue.</p>');
                                    }
                                    return new HtmlString('<p class="text-sm text-success-600">✓ You have sufficient credits to purchase this package.</p>');
                                })
                                ->visible(fn () => app(ActiveBusiness::class)->getActiveBusinessId() !== null),
                        ])
                        ->columns(1),
                ])
                ->action(function (array $data) {
                    try {
                        $record = $this->getRecord();
                        $user = auth()->user();
                        
                        // Validate business ownership
                        $business = $user->businesses()->find($data['business_id']);
                        if (!$business) {
                            Notification::make()
                                ->danger()
                                ->title('Invalid Business')
                                ->body('The selected business was not found or you do not have access to it.')
                                ->send();
                            return;
                        }
                        
                        // Get wallet and check credits
                        $wallet = Wallet::where('business_id', $data['business_id'])->first();
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
                        DB::beginTransaction();
                        
                        try {
                            // Create the campaign
                            $campaign = $record->createCampaign(
                                $data['business_id'],
                                $user->id,
                                [
                                    'starts_at' => $data['start_date'],
                                    'ends_at' => now()->parse($data['start_date'])->addDays($record->duration_days),
                                    'description' => $data['notes'] ?? null,
                                    'is_paid' => true,
                                    'is_active' => true, // Activate immediately after credit deduction
                                ]
                            );
                            
                            // Deduct credits from wallet
                            $wallet->useCredits(
                                $creditsRequired,
                                "Ad package purchase: {$record->name} ({$record->campaign_type})",
                                $campaign
                            );
                            
                            DB::commit();
                            
                            Notification::make()
                                ->success()
                                ->title('Campaign Created Successfully!')
                                ->body("Your campaign has been created and activated. {$creditsRequired} credits have been deducted from your wallet.")
                                ->send();
                            
                            $this->redirect(
                                \App\Filament\Business\Resources\AdCampaignResource::getUrl('view', ['record' => $campaign->id])
                            );
                            
                        } catch (\Exception $e) {
                            DB::rollBack();
                            throw $e;
                        }

                    } catch (\Exception $e) {
                        Log::error('Ad package purchase failed', [
                            'package_id' => $this->getRecord()->id,
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
                ->modalDescription(function () {
                    $record = $this->getRecord();
                    $businessId = app(ActiveBusiness::class)->getActiveBusinessId();
                    $wallet = $businessId ? Wallet::where('business_id', $businessId)->first() : null;
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

            Actions\Action::make('back')
                ->label('Back to Packages')
                ->icon('heroicon-o-arrow-left')
                ->url(route('filament.business.resources.ad-packages.index'))
                ->color('gray'),
        ];
    }
}