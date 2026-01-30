<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeolocationService
{
    /**
     * Get geolocation data for an IP address
     * Uses free ip-api.com service with caching
     * 
     * @param string|null $ipAddress
     * @return array
     */
    public static function getLocationData(?string $ipAddress = null): array
    {
        $ipAddress = $ipAddress ?? request()->ip();
        
        // Return default for localhost/internal IPs
        if (self::isLocalIp($ipAddress)) {
            return self::getDefaultLocation();
        }
        
        // Check cache first (cache for 24 hours)
        $cacheKey = "geolocation:{$ipAddress}";
        
        return Cache::remember($cacheKey, 86400, function () use ($ipAddress) {
            try {
                // Using ip-api.com (free, no API key required, 45 requests/minute)
                $response = Http::timeout(3)->get("http://ip-api.com/json/{$ipAddress}", [
                    'fields' => 'status,country,countryCode,region,regionName,city,lat,lon,timezone'
                ]);
                
                if ($response->successful() && $response->json('status') === 'success') {
                    $data = $response->json();
                    
                    return [
                        'country' => $data['country'] ?? 'Unknown',
                        'country_code' => $data['countryCode'] ?? null,
                        'region' => $data['regionName'] ?? 'Unknown',
                        'city' => $data['city'] ?? 'Unknown',
                        'latitude' => $data['lat'] ?? null,
                        'longitude' => $data['lon'] ?? null,
                        'timezone' => $data['timezone'] ?? null,
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('Geolocation API failed', [
                    'ip' => $ipAddress,
                    'error' => $e->getMessage()
                ]);
            }
            
            return self::getDefaultLocation();
        });
    }
    
    /**
     * Check if IP is local/internal
     */
    private static function isLocalIp(string $ip): bool
    {
        if (in_array($ip, ['127.0.0.1', '::1', 'localhost'])) {
            return true;
        }
        
        // Check private IP ranges
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return true;
        }
        
        return false;
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
