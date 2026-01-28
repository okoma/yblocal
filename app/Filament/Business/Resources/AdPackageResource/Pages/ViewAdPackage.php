<?php
// ============================================
// app/Filament/Business/Resources/AdPackageResource/Pages/ViewAdPackage.php
// ============================================

namespace App\Filament\Business\Resources\AdPackageResource\Pages;

use App\Filament\Business\Resources\AdPackageResource;
use App\Models\Business;
use App\Models\PaymentGateway;
use App\Services\ActiveBusiness;
use App\Services\PaymentService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
                        ->searchable()
                        ->preload()
                        ->helperText('Active business for this campaign'),

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
                    
                    Forms\Components\Select::make('payment_gateway_id')
                        ->label('Payment Method')
                        ->options(function () {
                            return PaymentGateway::enabled()
                                ->ordered()
                                ->get()
                                ->mapWithKeys(function ($gateway) {
                                    return [$gateway->id => $gateway->display_name];
                                });
                        })
                        ->required()
                        ->searchable()
                        ->preload()
                        ->helperText('Select how you want to pay for this package'),
                    
                    Forms\Components\Placeholder::make('package_price')
                        ->label('Package Price')
                        ->content(fn () => '₦' . number_format($this->getRecord()->price, 2))
                        ->extraAttributes(['class' => 'text-lg font-bold']),
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
                        
                        // Use database transaction
                        DB::beginTransaction();
                        
                        try {
                            // Create the campaign (initially unpaid)
                            $campaign = $record->createCampaign(
                                $data['business_id'],
                                $user->id,
                                [
                                    'starts_at' => $data['start_date'],
                                    'ends_at' => now()->parse($data['start_date'])->addDays($record->duration_days),
                                    'description' => $data['notes'] ?? null,
                                    'is_paid' => false,
                                    'is_active' => false, // Inactive until payment
                                ]
                            );
                            
                            // Initialize payment
                            $paymentService = app(PaymentService::class);
                            $result = $paymentService->initializePayment(
                                user: $user,
                                amount: $record->price,
                                gatewayId: $data['payment_gateway_id'],
                                payable: $campaign,
                                metadata: [
                                    'package_id' => $record->id,
                                    'package_name' => $record->name,
                                    'duration_days' => $record->duration_days,
                                    'campaign_type' => $record->campaign_type,
                                ]
                            );
                            
                            DB::commit();
                            
                            // Handle payment result
                            if ($result->requiresRedirect()) {
                                Notification::make()
                                    ->info()
                                    ->title('Redirecting to Payment')
                                    ->body('You will be redirected to complete your payment.')
                                    ->send();
                                
                                return redirect($result->redirectUrl);
                            } elseif ($result->isBankTransfer()) {
                                Notification::make()
                                    ->info()
                                    ->title('Bank Transfer Instructions')
                                    ->body($result->instructions)
                                    ->persistent()
                                    ->send();
                                
                                // Redirect to campaign view
                                $this->redirect(
                                    \App\Filament\Business\Resources\AdCampaignResource::getUrl('view', ['record' => $campaign->id])
                                );
                            } elseif ($result->isSuccess()) {
                                // Payment successful (wallet payment)
                                // Campaign will be activated automatically via PaymentController
                                Notification::make()
                                    ->success()
                                    ->title('Payment Successful')
                                    ->body('Your campaign has been created and activated!')
                                    ->send();
                                
                                $this->redirect(
                                    \App\Filament\Business\Resources\AdCampaignResource::getUrl('view', ['record' => $campaign->id])
                                );
                            } else {
                                Notification::make()
                                    ->danger()
                                    ->title('Payment Failed')
                                    ->body($result->message ?? 'Unable to process payment. Please try again.')
                                    ->send();
                            }
                            
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
                ->modalDescription(fn () => 'You are about to purchase the "' . $this->getRecord()->name . '" package for ₦' . number_format($this->getRecord()->price, 2))
                ->modalSubmitActionLabel('Purchase & Pay')
                ->modalFooterActionsAlignment('right'),

            Actions\Action::make('back')
                ->label('Back to Packages')
                ->icon('heroicon-o-arrow-left')
                ->url(route('filament.business.resources.ad-packages.index'))
                ->color('gray'),
        ];
    }
}