<?php

namespace App\Filament\Admin\Pages;

use App\Models\PaymentGateway;
use Filament\Forms;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class PaymentSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    
    protected static ?string $navigationLabel = 'Payment Settings';
    
    protected static ?string $navigationGroup = 'Settings';
    
    protected static ?int $navigationSort = 20;

    protected static string $view = 'filament.admin.pages.payment-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->loadGateways();
    }

    protected function loadGateways(): void
    {
        $gateways = PaymentGateway::all()->keyBy('slug');
        
        $this->data = [
            'paystack' => [
                'is_active' => $gateways->get('paystack')?->is_active ?? false,
                'is_enabled' => $gateways->get('paystack')?->is_enabled ?? false,
                'public_key' => $gateways->get('paystack')?->public_key ?? '',
                'secret_key' => $gateways->get('paystack')?->secret_key ?? '',
            ],
            'flutterwave' => [
                'is_active' => $gateways->get('flutterwave')?->is_active ?? false,
                'is_enabled' => $gateways->get('flutterwave')?->is_enabled ?? false,
                'public_key' => $gateways->get('flutterwave')?->public_key ?? '',
                'secret_key' => $gateways->get('flutterwave')?->secret_key ?? '',
            ],
            'bank_transfer' => [
                'is_active' => $gateways->get('bank_transfer')?->is_active ?? false,
                'is_enabled' => $gateways->get('bank_transfer')?->is_enabled ?? false,
                'account_name' => $gateways->get('bank_transfer')?->bank_account_details['account_name'] ?? '',
                'account_number' => $gateways->get('bank_transfer')?->bank_account_details['account_number'] ?? '',
                'bank_name' => $gateways->get('bank_transfer')?->bank_account_details['bank_name'] ?? '',
                'sort_code' => $gateways->get('bank_transfer')?->bank_account_details['sort_code'] ?? '',
                'instructions' => $gateways->get('bank_transfer')?->instructions ?? '',
            ],
            'wallet' => [
                'is_active' => $gateways->get('wallet')?->is_active ?? true,
                'is_enabled' => $gateways->get('wallet')?->is_enabled ?? true,
                'description' => $gateways->get('wallet')?->description ?? '',
                'instructions' => $gateways->get('wallet')?->instructions ?? '',
            ],
        ];

        $this->form->fill($this->data);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('PaymentGateways')
                    ->tabs([
                        Tabs\Tab::make('Paystack')
                            ->schema([
                                Forms\Components\Section::make('Paystack Configuration')
                                    ->description('Configure Paystack payment gateway. Webhooks are automatically handled at /webhooks/paystack')
                                    ->schema([
                                        Forms\Components\Toggle::make('paystack.is_active')
                                            ->label('Active')
                                            ->helperText('Show Paystack as payment option'),
                                        
                                        Forms\Components\Toggle::make('paystack.is_enabled')
                                            ->label('Enabled & Configured')
                                            ->helperText('Paystack is fully configured and ready'),
                                        
                                        Forms\Components\TextInput::make('paystack.public_key')
                                            ->label('Public Key')
                                            ->maxLength(255)
                                            ->required(fn (Forms\Get $get) => $get('paystack.is_enabled'))
                                            ->helperText('Your Paystack public key (starts with pk_)'),
                                        
                                        Forms\Components\TextInput::make('paystack.secret_key')
                                            ->label('Secret Key')
                                            ->password()
                                            ->maxLength(255)
                                            ->required(fn (Forms\Get $get) => $get('paystack.is_enabled'))
                                            ->helperText('Your Paystack secret key (starts with sk_)'),
                                        
                                        Forms\Components\Placeholder::make('paystack_callback_info')
                                            ->label('Callback URL')
                                            ->content(fn () => url('/payment/paystack/callback'))
                                            ->helperText('URL where users are redirected after payment. This is automatically used when initializing payments.'),
                                        
                                        Forms\Components\Placeholder::make('paystack_webhook_info')
                                            ->label('Webhook URL')
                                            ->content(fn () => url('/webhooks/paystack'))
                                            ->helperText('Configure this URL in your Paystack dashboard'),
                                    ])
                                    ->columns(2),
                            ]),
                        
                        Tabs\Tab::make('Flutterwave')
                            ->schema([
                                Forms\Components\Section::make('Flutterwave Configuration')
                                    ->description('Configure Flutterwave payment gateway. Webhooks are automatically handled at /webhooks/flutterwave')
                                    ->schema([
                                        Forms\Components\Toggle::make('flutterwave.is_active')
                                            ->label('Active')
                                            ->helperText('Show Flutterwave as payment option'),
                                        
                                        Forms\Components\Toggle::make('flutterwave.is_enabled')
                                            ->label('Enabled & Configured')
                                            ->helperText('Flutterwave is fully configured and ready'),
                                        
                                        Forms\Components\TextInput::make('flutterwave.public_key')
                                            ->label('Public Key')
                                            ->maxLength(255)
                                            ->required(fn (Forms\Get $get) => $get('flutterwave.is_enabled'))
                                            ->helperText('Your Flutterwave public key'),
                                        
                                        Forms\Components\TextInput::make('flutterwave.secret_key')
                                            ->label('Secret Key')
                                            ->password()
                                            ->maxLength(255)
                                            ->required(fn (Forms\Get $get) => $get('flutterwave.is_enabled'))
                                            ->helperText('Your Flutterwave secret key'),
                                        
                                        Forms\Components\Placeholder::make('flutterwave_callback_info')
                                            ->label('Callback URL')
                                            ->content(fn () => url('/payment/flutterwave/callback'))
                                            ->helperText('URL where users are redirected after payment. This is automatically used when initializing payments.'),
                                        
                                        Forms\Components\Placeholder::make('flutterwave_webhook_info')
                                            ->label('Webhook URL')
                                            ->content(fn () => url('/webhooks/flutterwave'))
                                            ->helperText('Configure this URL in your Flutterwave dashboard'),
                                    ])
                                    ->columns(2),
                            ]),
                        
                        Tabs\Tab::make('Bank Transfer')
                            ->schema([
                                Forms\Components\Section::make('Bank Transfer Configuration')
                                    ->schema([
                                        Forms\Components\Toggle::make('bank_transfer.is_active')
                                            ->label('Active')
                                            ->helperText('Show Bank Transfer as payment option'),
                                        
                                        Forms\Components\Toggle::make('bank_transfer.is_enabled')
                                            ->label('Enabled & Configured')
                                            ->helperText('Bank transfer is configured and ready'),
                                        
                                        Forms\Components\TextInput::make('bank_transfer.account_name')
                                            ->label('Account Name')
                                            ->required(fn (Forms\Get $get) => $get('bank_transfer.is_enabled'))
                                            ->maxLength(255),
                                        
                                        Forms\Components\TextInput::make('bank_transfer.account_number')
                                            ->label('Account Number')
                                            ->required(fn (Forms\Get $get) => $get('bank_transfer.is_enabled'))
                                            ->maxLength(20),
                                        
                                        Forms\Components\TextInput::make('bank_transfer.bank_name')
                                            ->label('Bank Name')
                                            ->required(fn (Forms\Get $get) => $get('bank_transfer.is_enabled'))
                                            ->maxLength(255),
                                        
                                        Forms\Components\TextInput::make('bank_transfer.sort_code')
                                            ->label('Sort Code')
                                            ->maxLength(20)
                                            ->helperText('Optional'),
                                        
                                        Forms\Components\Textarea::make('bank_transfer.instructions')
                                            ->label('Instructions')
                                            ->rows(4)
                                            ->maxLength(1000)
                                            ->helperText('Instructions for users making bank transfers'),
                                    ])
                                    ->columns(2),
                            ]),
                        
                        Tabs\Tab::make('Wallet Payment')
                            ->schema([
                                Forms\Components\Section::make('Wallet Payment Configuration')
                                    ->schema([
                                        Forms\Components\Toggle::make('wallet.is_active')
                                            ->label('Active')
                                            ->helperText('Show Wallet Payment as payment option'),
                                        
                                        Forms\Components\Toggle::make('wallet.is_enabled')
                                            ->label('Enabled')
                                            ->helperText('Wallet payment is enabled'),
                                        
                                        Forms\Components\Textarea::make('wallet.description')
                                            ->label('Description')
                                            ->rows(3)
                                            ->maxLength(500),
                                        
                                        Forms\Components\Textarea::make('wallet.instructions')
                                            ->label('Instructions')
                                            ->rows(3)
                                            ->maxLength(1000),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Update or create Paystack
        $this->updateGateway('paystack', $data['paystack'] ?? []);
        
        // Update or create Flutterwave
        $this->updateGateway('flutterwave', $data['flutterwave'] ?? []);
        
        // Update or create Bank Transfer
        $this->updateGateway('bank_transfer', $data['bank_transfer'] ?? []);
        
        // Update or create Wallet
        $this->updateGateway('wallet', $data['wallet'] ?? []);

        Notification::make()
            ->success()
            ->title('Payment settings saved')
            ->body('Payment gateway configurations have been updated.')
            ->send();
    }

    protected function updateGateway(string $slug, array $data): void
    {
        $gateway = PaymentGateway::firstOrNew(['slug' => $slug]);
        
        if (!$gateway->exists) {
            $gateway->name = ucfirst(str_replace('_', ' ', $slug));
            $gateway->display_name = ucfirst(str_replace('_', ' ', $slug));
        }
        
        $gateway->is_active = $data['is_active'] ?? false;
        $gateway->is_enabled = $data['is_enabled'] ?? false;
        
        if ($slug === 'paystack') {
            $gateway->public_key = $data['public_key'] ?? null;
            $gateway->secret_key = $data['secret_key'] ?? null;
            // Callback URL is always the default route, no need to store it
            $gateway->callback_url = null;
            // Clear unused fields
            $gateway->merchant_id = null;
            $gateway->webhook_url = null;
        }
        
        if ($slug === 'flutterwave') {
            $gateway->public_key = $data['public_key'] ?? null;
            $gateway->secret_key = $data['secret_key'] ?? null;
            // Callback URL is always the default route, no need to store it
            $gateway->callback_url = null;
            // Clear unused fields
            $gateway->merchant_id = null;
            $gateway->webhook_url = null;
        }
        
        if ($slug === 'bank_transfer') {
            $gateway->bank_account_details = [
                'account_name' => $data['account_name'] ?? '',
                'account_number' => $data['account_number'] ?? '',
                'bank_name' => $data['bank_name'] ?? '',
                'sort_code' => $data['sort_code'] ?? '',
            ];
            $gateway->instructions = $data['instructions'] ?? null;
        }
        
        if ($slug === 'wallet') {
            $gateway->description = $data['description'] ?? null;
            $gateway->instructions = $data['instructions'] ?? null;
        }
        
        $gateway->save();
    }
}
