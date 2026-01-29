<?php

namespace App\Filament\Business\Pages;

use App\Models\BusinessReferral;
use App\Models\BusinessReferralCreditTransaction;
use App\Services\ActiveBusiness;
use App\Services\ReferralCreditConversionService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class ReferralsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    protected static ?string $navigationLabel = 'Referrals';
    protected static ?string $navigationGroup = 'Billing & Marketing';
    protected static ?int $navigationSort = 12;
    protected static string $view = 'filament.business.pages.referrals';
    protected static bool $shouldRegisterNavigation = true;

    public function getTitle(): string
    {
        return 'Referrals';
    }

    public function getActiveBusiness()
    {
        return app(ActiveBusiness::class)->getActiveBusiness();
    }

    public function getReferralLink(): string
    {
        $business = $this->getActiveBusiness();
        $code = $business?->referral_code;
        if (!$code) {
            return '';
        }
        return config('referral.business_register_url', 'https://biz.yellowbooks.ng/register') . '?ref=' . urlencode($code);
    }

    public function getReferredBusinesses()
    {
        $business = $this->getActiveBusiness();
        if (!$business) {
            return collect();
        }
        return BusinessReferral::where('referrer_business_id', $business->id)
            ->with('referredBusiness')
            ->orderByDesc('created_at')
            ->get();
    }

    public function getConversionHistory()
    {
        $business = $this->getActiveBusiness();
        if (!$business) {
            return collect();
        }
        return BusinessReferralCreditTransaction::where('business_id', $business->id)
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();
    }

    public function getCreditsEarnedByMonth(): array
    {
        $business = $this->getActiveBusiness();
        if (!$business) {
            return [];
        }
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            return BusinessReferralCreditTransaction::where('business_id', $business->id)
                ->where('type', BusinessReferralCreditTransaction::TYPE_EARNED)
                ->where('amount', '>', 0)
                ->selectRaw('strftime("%Y-%m", created_at) as month, SUM(amount) as total')
                ->groupBy('month')
                ->orderByDesc('month')
                ->limit(12)
                ->pluck('total', 'month')
                ->all();
        }
        return BusinessReferralCreditTransaction::where('business_id', $business->id)
            ->where('type', BusinessReferralCreditTransaction::TYPE_EARNED)
            ->where('amount', '>', 0)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(amount) as total")
            ->groupBy('month')
            ->orderByDesc('month')
            ->limit(12)
            ->pluck('total', 'month')
            ->all();
    }

    protected function getHeaderActions(): array
    {
        $business = $this->getActiveBusiness();
        if (!$business) {
            return [];
        }
        $conversionService = app(ReferralCreditConversionService::class);
        $creditsRequired = (int) config('referral.conversion_to_subscription_credits', 500);

        return [
            Action::make('convertToAdCredits')
                ->label('Convert to Ad Credits')
                ->icon('heroicon-o-megaphone')
                ->form([
                    Forms\Components\TextInput::make('credits')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->live()
                        ->helperText(fn () => 'Available: ' . ($this->getActiveBusiness()->referral_credits ?? 0) . ' referral credits (1:1)'),
                ])
                ->action(function (array $data) use ($conversionService) {
                    $business = $this->getActiveBusiness();
                    if (!$business) {
                        Notification::make()->title('No active business')->danger()->send();
                        return;
                    }
                    $result = $conversionService->convertToAdCredits($business, (int) $data['credits']);
                    Notification::make()->title($result['success'] ? $result['message'] : $result['message'])->{$result['success'] ? 'success' : 'danger'}()->send();
                })
                ->visible(fn () => ($this->getActiveBusiness()->referral_credits ?? 0) > 0),

            Action::make('convertToQuoteCredits')
                ->label('Convert to Quote Credits')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->form([
                    Forms\Components\TextInput::make('credits')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->live()
                        ->helperText(fn () => 'Available: ' . ($this->getActiveBusiness()->referral_credits ?? 0) . ' referral credits (1:1)'),
                ])
                ->action(function (array $data) use ($conversionService) {
                    $business = $this->getActiveBusiness();
                    if (!$business) {
                        Notification::make()->title('No active business')->danger()->send();
                        return;
                    }
                    $result = $conversionService->convertToQuoteCredits($business, (int) $data['credits']);
                    Notification::make()->title($result['success'] ? $result['message'] : $result['message'])->{$result['success'] ? 'success' : 'danger'}()->send();
                })
                ->visible(fn () => ($this->getActiveBusiness()->referral_credits ?? 0) > 0),

            Action::make('convertToSubscription')
                ->label('Convert to 1 Month Subscription')
                ->icon('heroicon-o-calendar')
                ->requiresConfirmation()
                ->modalHeading('Redeem 1 month subscription')
                ->modalDescription("This will use {$creditsRequired} referral credits to extend your subscription by 1 month.")
                ->action(function () use ($conversionService) {
                    $business = $this->getActiveBusiness();
                    if (!$business) {
                        Notification::make()->title('No active business')->danger()->send();
                        return;
                    }
                    $result = $conversionService->convertToSubscription($business);
                    Notification::make()->title($result['success'] ? $result['message'] : $result['message'])->{$result['success'] ? 'success' : 'danger'}()->send();
                })
                ->visible(fn () => ($this->getActiveBusiness()->referral_credits ?? 0) >= $creditsRequired),
        ];
    }
}
