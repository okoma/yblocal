<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class GeolocationService
{
    /**
     * Get geolocation data from Cloudflare headers
     * Cloudflare automatically adds geo headers when traffic passes through their network
     * 
     * @param string|null $ipAddress (unused, kept for backward compatibility)
     * @return array
     */
    public static function getLocationData(?string $ipAddress = null): array
    {
        // Check if Cloudflare headers are present
        $countryCode = request()->header('CF-IPCountry');
        
        if ($countryCode && $countryCode !== 'XX') {
            return [
                'country' => self::getCountryName($countryCode),
                'country_code' => $countryCode,
                'region' => request()->header('CF-Region') ?: 'Unknown',
                'city' => self::decodeCloudflareCity(request()->header('CF-IPCity')) ?: 'Unknown',
                'latitude' => request()->header('CF-Latitude') ?: null,
                'longitude' => request()->header('CF-Longitude') ?: null,
                'timezone' => request()->header('CF-Timezone') ?: null,
            ];
        }
        
        // Fallback if Cloudflare headers not available
        Log::info('Cloudflare headers not found, using default location', [
            'ip' => $ipAddress ?? request()->ip()
        ]);
        
        return self::getDefaultLocation();
    }
    
    /**
     * Decode Cloudflare city name (URL encoded)
     */
    private static function decodeCloudflareCity(?string $city): ?string
    {
        if (!$city) {
            return null;
        }
        
        return urldecode($city);
    }
    
    /**
     * Get country name from country code
     * Using common countries first, extend as needed
     */
    private static function getCountryName(string $countryCode): string
    {
        $countries = [
            'NG' => 'Nigeria',
            'US' => 'United States',
            'GB' => 'United Kingdom',
            'CA' => 'Canada',
            'AU' => 'Australia',
            'ZA' => 'South Africa',
            'KE' => 'Kenya',
            'GH' => 'Ghana',
            'IN' => 'India',
            'DE' => 'Germany',
            'FR' => 'France',
            'BR' => 'Brazil',
            'AE' => 'United Arab Emirates',
            'SA' => 'Saudi Arabia',
            'EG' => 'Egypt',
            'CN' => 'China',
            'JP' => 'Japan',
            'SG' => 'Singapore',
            'NL' => 'Netherlands',
            'IT' => 'Italy',
            'ES' => 'Spain',
            'MX' => 'Mexico',
            'AR' => 'Argentina',
            'CO' => 'Colombia',
            'PH' => 'Philippines',
            'TH' => 'Thailand',
            'MY' => 'Malaysia',
            'ID' => 'Indonesia',
            'PK' => 'Pakistan',
            'BD' => 'Bangladesh',
            'VN' => 'Vietnam',
            'TR' => 'Turkey',
            'PL' => 'Poland',
            'UA' => 'Ukraine',
            'RO' => 'Romania',
            'BE' => 'Belgium',
            'SE' => 'Sweden',
            'CH' => 'Switzerland',
            'AT' => 'Austria',
            'NO' => 'Norway',
            'DK' => 'Denmark',
            'FI' => 'Finland',
            'IE' => 'Ireland',
            'PT' => 'Portugal',
            'GR' => 'Greece',
            'CZ' => 'Czech Republic',
            'HU' => 'Hungary',
            'NZ' => 'New Zealand',
            'IL' => 'Israel',
            'CL' => 'Chile',
            'PE' => 'Peru',
            'VE' => 'Venezuela',
            'UY' => 'Uruguay',
        ];
        
        return $countries[$countryCode] ?? $countryCode;
    }
    
    /**
     * Get default location for local/unknown IPs
     */
    private static function getDefaultLocation(): array
    {
        return [
            'country' => 'Nigeria', // Default country for YBLocal
            'country_code' => 'NG',
            'region' => 'Lagos',
            'city' => 'Lagos',
            'latitude' => null,
            'longitude' => null,
            'timezone' => 'Africa/Lagos',
        ];
    }
    
    /**
     * Get country flag emoji from country code
     * 
     * @param string|null $countryCode ISO 3166-1 alpha-2 country code
     * @return string
     */
    public static function getCountryFlag(?string $countryCode): string
    {
        if (!$countryCode || strlen($countryCode) !== 2) {
            return '🌍'; // Globe emoji for unknown
        }
        
        $countryCode = strtoupper($countryCode);
        
        // Convert country code to flag emoji
        // Flag emojis are created by combining Regional Indicator Symbol letters
        $firstLetter = mb_chr(ord($countryCode[0]) - ord('A') + 0x1F1E6);
        $secondLetter = mb_chr(ord($countryCode[1]) - ord('A') + 0x1F1E6);
        
        return $firstLetter . $secondLetter;
    }
    
    /**
     * Get formatted location string with flag
     * 
     * @param string|null $city
     * @param string|null $country
     * @param string|null $countryCode
     * @return string
     */
    public static function getFormattedLocation(?string $city, ?string $country, ?string $countryCode): string
    {
        $flag = self::getCountryFlag($countryCode);
        $parts = array_filter([$city, $country]);
        
        if (empty($parts)) {
            return "{$flag} Unknown";
        }
        
        return $flag . ' ' . implode(', ', $parts);
    }
}
