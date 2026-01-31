<?php

namespace App\Enums;

enum PriceTier: string
{
    case BUDGET = 'budget';           // ₦
    case AFFORDABLE = 'affordable';   // ₦₦
    case PREMIUM = 'premium';         // ₦₦₦
    case LUXURY = 'luxury';           // ₦₦₦₦

    /**
     * Get the display label for the price tier
     */
    public function label(): string
    {
        return match ($this) {
            self::BUDGET => '₦ Budget',
            self::AFFORDABLE => '₦₦ Affordable',
            self::PREMIUM => '₦₦₦ Premium',
            self::LUXURY => '₦₦₦₦ Luxury',
        };
    }

    /**
     * Get the symbols for the price tier (for display)
     */
    public function symbol(): string
    {
        return match ($this) {
            self::BUDGET => '₦',
            self::AFFORDABLE => '₦₦',
            self::PREMIUM => '₦₦₦',
            self::LUXURY => '₦₦₦₦',
        };
    }

    /**
     * Get all options for select dropdowns
     */
    public static function options(): array
    {
        return array_combine(
            array_map(fn($case) => $case->value, self::cases()),
            array_map(fn($case) => $case->label(), self::cases())
        );
    }
}
