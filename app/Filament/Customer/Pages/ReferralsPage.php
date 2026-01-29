<?php

namespace App\Filament\Customer\Pages;

use App\Models\CustomerReferral;
use App\Models\CustomerReferralTransaction;
use App\Models\CustomerReferralWithdrawal;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReferralsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    protected static ?string $navigationLabel = 'Referrals';
    protected static ?string $navigationGroup = 'Earn';
    protected static ?int $navigationSort = 10;
    protected static string $view = 'filament.customer.pages.referrals';
    protected static bool $shouldRegisterNavigation = true;

    public ?array $withdrawalData = [];

    public function getTitle(): string
    {
        return 'Invite Businesses & Earn';
    }

    public function getReferralLink(): string
    {
        $code = Auth::user()?->referral_code;
        if (!$code) {
            return '';
        }
        return config('referral.business_register_url', 'https://biz.yellowbooks.ng/register') . '?ref=' . urlencode($code);
    }

    public function getWallet()
    {
        return Auth::user()?->getOrCreateCustomerReferralWallet();
    }

    public function getReferredBusinesses()
    {
        return CustomerReferral::where('referrer_user_id', Auth::id())
            ->with('referredBusiness')
            ->orderByDesc('created_at')
            ->get();
    }

    public function getCommissionEarningsByMonth(): array
    {
        $wallet = $this->getWallet();
        if (!$wallet) {
            return [];
        }
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            return CustomerReferralTransaction::where('customer_referral_wallet_id', $wallet->id)
                ->where('type', 'commission')
                ->where('amount', '>', 0)
                ->selectRaw('strftime("%Y-%m", created_at) as month, SUM(amount) as total')
                ->groupBy('month')
                ->orderByDesc('month')
                ->limit(12)
                ->pluck('total', 'month')
                ->map(fn ($v) => (float) $v)
                ->all();
        }
        return CustomerReferralTransaction::where('customer_referral_wallet_id', $wallet->id)
            ->where('type', 'commission')
            ->where('amount', '>', 0)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(amount) as total")
            ->groupBy('month')
            ->orderByDesc('month')
            ->limit(12)
            ->pluck('total', 'month')
            ->map(fn ($v) => (float) $v)
            ->all();
    }

    public function getWithdrawals()
    {
        return CustomerReferralWithdrawal::where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();
    }

    public function getTotalCommissionEarned(): float
    {
        $wallet = $this->getWallet();
        if (!$wallet) {
            return 0;
        }
        return (float) CustomerReferralTransaction::where('customer_referral_wallet_id', $wallet->id)
            ->where('type', 'commission')
            ->where('amount', '>', 0)
            ->sum('amount');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('requestWithdrawal')
                ->label('Request Withdrawal')
                ->icon('heroicon-o-banknotes')
                ->form([
                    Forms\Components\TextInput::make('amount')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->prefix('₦')
                        ->helperText('Available: ₦' . number_format($this->getWallet()?->balance ?? 0, 2)),
                    Forms\Components\TextInput::make('bank_name')->required()->maxLength(255),
                    Forms\Components\TextInput::make('account_name')->required()->maxLength(255),
                    Forms\Components\TextInput::make('account_number')->required()->maxLength(20),
                    Forms\Components\TextInput::make('sort_code')->maxLength(20),
                ])
                ->action(function (array $data) {
                    $wallet = $this->getWallet();
                    if (!$wallet || (float) $wallet->balance < (float) $data['amount']) {
                        Notification::make()->title('Insufficient balance')->danger()->send();
                        return;
                    }
                    CustomerReferralWithdrawal::create([
                        'user_id' => Auth::id(),
                        'customer_referral_wallet_id' => $wallet->id,
                        'amount' => $data['amount'],
                        'bank_name' => $data['bank_name'],
                        'account_name' => $data['account_name'],
                        'account_number' => $data['account_number'],
                        'sort_code' => $data['sort_code'] ?? null,
                        'status' => 'pending',
                    ]);
                    Notification::make()->title('Withdrawal requested')->body('Your request will be processed by our team.')->success()->send();
                }),
        ];
    }
}
