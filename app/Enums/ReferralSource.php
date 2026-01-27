<?php
// ============================================
// app/Enums/ReferralSource.php
// Enum for tracking where visitors come from
// ============================================

namespace App\Enums;

enum ReferralSource: string
{
    case YELLOWBOOKS = 'yellowbooks';
    case GOOGLE = 'google';
    case BING = 'bing';
    case FACEBOOK = 'facebook';
    case INSTAGRAM = 'instagram';
    case TWITTER = 'twitter';
    case LINKEDIN = 'linkedin';
    case DIRECT = 'direct';
    case OTHER = 'other';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match($this) {
            self::YELLOWBOOKS => 'YellowBooks',
            self::GOOGLE => 'Google',
            self::BING => 'Bing',
            self::FACEBOOK => 'Facebook',
            self::INSTAGRAM => 'Instagram',
            self::TWITTER => 'Twitter/X',
            self::LINKEDIN => 'LinkedIn',
            self::DIRECT => 'Direct Visit',
            self::OTHER => 'Other',
        };
    }

    /**
     * Get icon class (for frontend display)
     */
    public function icon(): string
    {
        return match($this) {
            self::YELLOWBOOKS => 'heroicon-o-home',
            self::GOOGLE => 'fab-google',
            self::BING => 'fab-microsoft',
            self::FACEBOOK => 'fab-facebook',
            self::INSTAGRAM => 'fab-instagram',
            self::TWITTER => 'fab-x-twitter',
            self::LINKEDIN => 'fab-linkedin',
            self::DIRECT => 'heroicon-o-link',
            self::OTHER => 'heroicon-o-globe-alt',
        };
    }

    /**
     * Get color class (for badges/charts)
     */
    public function color(): string
    {
        return match($this) {
            self::YELLOWBOOKS => 'warning',
            self::GOOGLE => 'danger',
            self::BING => 'info',
            self::FACEBOOK => 'primary',
            self::INSTAGRAM => 'danger',
            self::TWITTER => 'info',
            self::LINKEDIN => 'primary',
            self::DIRECT => 'success',
            self::OTHER => 'gray',
        };
    }

    /**
     * Check if this is a social media source
     */
    public function isSocialMedia(): bool
    {
        return in_array($this, [
            self::FACEBOOK,
            self::INSTAGRAM,
            self::TWITTER,
            self::LINKEDIN,
        ]);
    }

    /**
     * Check if this is a search engine
     */
    public function isSearchEngine(): bool
    {
        return in_array($this, [
            self::GOOGLE,
            self::BING,
        ]);
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