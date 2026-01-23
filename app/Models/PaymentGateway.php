<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentGateway extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'display_name',
        'description',
        'is_active',
        'is_enabled',
        'sort_order',
        'settings',
        'public_key',
        'secret_key',
        'merchant_id',
        'webhook_url',
        'callback_url',
        'bank_account_details',
        'supported_currencies',
        'supported_payment_methods',
        'instructions',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_enabled' => 'boolean',
        'settings' => 'array',
        'bank_account_details' => 'array',
        'supported_currencies' => 'array',
        'supported_payment_methods' => 'array',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_active', true)->where('is_enabled', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // Helper methods
    public function isPaystack(): bool
    {
        return $this->slug === 'paystack';
    }

    public function isFlutterwave(): bool
    {
        return $this->slug === 'flutterwave';
    }

    public function isBankTransfer(): bool
    {
        return $this->slug === 'bank_transfer';
    }

    public function isWallet(): bool
    {
        return $this->slug === 'wallet';
    }

    public function getSetting(string $key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }

    public function setSetting(string $key, $value): void
    {
        $settings = $this->settings ?? [];
        $settings[$key] = $value;
        $this->settings = $settings;
    }

    public function getSettings(): array
    {
        return $this->settings ?? [];
    }
}
