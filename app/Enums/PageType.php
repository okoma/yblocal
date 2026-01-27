<?php
namespace App\Enums;

enum PageType: string
{
    case ARCHIVE = 'archive';
    case CATEGORY = 'category';
    case SEARCH = 'search';
    case RELATED = 'related';
    case FEATURED = 'featured';
    case HOME = 'home';
    case LOCATION = 'location';        // ← ADD THIS
    case BUSINESS_TYPE = 'business_type';  // ← ADD THIS
    case OTHER = 'other';

    public function label(): string
    {
        return match($this) {
            self::ARCHIVE => 'Archive/Listing Page',
            self::CATEGORY => 'Category Page',
            self::SEARCH => 'Search Results',
            self::RELATED => 'Related Businesses',
            self::FEATURED => 'Featured Section',
            self::HOME => 'Home Page',
            self::LOCATION => 'Location Page',        // ← ADD THIS
            self::BUSINESS_TYPE => 'Business Type Page',  // ← ADD THIS
            self::OTHER => 'Other',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::ARCHIVE => 'heroicon-o-rectangle-stack',
            self::CATEGORY => 'heroicon-o-folder',
            self::SEARCH => 'heroicon-o-magnifying-glass',
            self::RELATED => 'heroicon-o-link',
            self::FEATURED => 'heroicon-o-star',
            self::HOME => 'heroicon-o-home',
            self::LOCATION => 'heroicon-o-map-pin',        // ← ADD THIS
            self::BUSINESS_TYPE => 'heroicon-o-briefcase',  // ← ADD THIS
            self::OTHER => 'heroicon-o-ellipsis-horizontal',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::ARCHIVE => 'primary',
            self::CATEGORY => 'success',
            self::SEARCH => 'info',
            self::RELATED => 'warning',
            self::FEATURED => 'danger',
            self::HOME => 'primary',
            self::LOCATION => 'info',        // ← ADD THIS
            self::BUSINESS_TYPE => 'success',  // ← ADD THIS
            self::OTHER => 'gray',
        };
    }

    public static function options(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn($case) => $case->label(), self::cases())
        );
    }
}