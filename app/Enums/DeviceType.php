<?php
// ============================================
// app/Enums/DeviceType.php
// Enum for tracking device types
// ============================================

namespace App\Enums;

enum DeviceType: string
{
    case MOBILE = 'mobile';
    case DESKTOP = 'desktop';
    case TABLET = 'tablet';
    case BOT = 'bot';
    case UNKNOWN = 'unknown';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match($this) {
            self::MOBILE => 'Mobile',
            self::DESKTOP => 'Desktop',
            self::TABLET => 'Tablet',
            self::BOT => 'Bot/Crawler',
            self::UNKNOWN => 'Unknown',
        };
    }

    /**
     * Get icon class
     */
    public function icon(): string
    {
        return match($this) {
            self::MOBILE => 'heroicon-o-device-phone-mobile',
            self::DESKTOP => 'heroicon-o-computer-desktop',
            self::TABLET => 'heroicon-o-device-tablet',
            self::BOT => 'heroicon-o-cpu-chip',
            self::UNKNOWN => 'heroicon-o-question-mark-circle',
        };
    }

    /**
     * Get color class
     */
    public function color(): string
    {
        return match($this) {
            self::MOBILE => 'success',
            self::DESKTOP => 'primary',
            self::TABLET => 'warning',
            self::BOT => 'danger',
            self::UNKNOWN => 'gray',
        };
    }

    /**
     * Detect device type from user agent
     */
    public static function detect(?string $userAgent): self
    {
        if (!$userAgent) {
            return self::UNKNOWN;
        }

        $userAgent = strtolower($userAgent);

        // Check for bots first
        if (preg_match('/bot|crawler|spider|crawling/i', $userAgent)) {
            return self::BOT;
        }

        // Check for tablets
        if (preg_match('/tablet|ipad|playbook|silk/i', $userAgent)) {
            return self::TABLET;
        }

        // Check for mobile
        if (preg_match('/mobile|android|iphone|ipod|blackberry|windows phone/i', $userAgent)) {
            return self::MOBILE;
        }

        // Default to desktop
        return self::DESKTOP;
    }

    /**
     * Get all values as array (for dropdowns)
     */
    public static function options(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn($case) => $case->label(), self::cases())
        );
    }
}