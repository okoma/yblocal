# Fraud Detection System - Implementation Summary

## Overview
Comprehensive fraud detection system for the referral ecosystem to prevent abuse, self-referrals, and bot-driven signups.

## Files Created/Modified

### 1. Migration: Add Fraud Detection Fields
**File:** `database/migrations/2026_01_30_153210_add_fraud_detection_to_referral_tables.php`

Added to both `business_referrals` and `customer_referrals` tables:
- `ip_address` (string, 45 chars, indexed) - IPv4/IPv6 tracking
- `device_fingerprint` (string, indexed) - Browser fingerprinting via client-side JS
- `user_agent` (string) - Browser/device identification
- `is_suspicious` (boolean, indexed) - Fraud flag for admin review
- `fraud_notes` (text) - Detailed reasons for flagging
- `verified_at` (timestamp) - Admin verification timestamp

### 2. FraudDetectionService
**File:** `app/Services/FraudDetectionService.php` (310 lines)

**Fraud Detection Thresholds:**
```php
MAX_REFERRALS_PER_IP_PER_DAY = 3
MAX_REFERRALS_PER_DEVICE_PER_DAY = 3
MAX_REFERRALS_PER_USER_PER_MONTH = 20
SUSPICIOUS_IP_PREFIX_LIMIT = 5  // /24 subnet limit
```

**Key Methods:**

#### checkReferralSignup()
Analyzes referral signup for fraud patterns:
- IP-based checks (exact IP + /24 subnet tracking)
- Device fingerprint analysis
- Referrer volume limits (monthly caps)
- Same-IP-as-referrer detection (self-referral)

Returns: `['is_suspicious' => bool, 'reasons' => array]`

#### recordReferralMetrics()
Tracks referral data in cache (24-hour TTL):
- Increments IP counter per day
- Increments subnet (/24) counter per day
- Increments device counter per day

Uses Redis/Cache for fast lookups without DB queries.

#### markAsSuspicious()
Flags a referral for admin review with detailed notes.

#### verifyReferral()
Admin action to clear fraud flag and mark as legitimate.

#### getSuspiciousReferrals()
Retrieves all unverified suspicious referrals for admin dashboard.

**Fraud Pattern Detection:**
1. **IP Abuse:** >3 referrals from same IP in 24 hours
2. **Subnet Fraud:** >5 referrals from same /24 subnet (distributed bots)
3. **Device Abuse:** >3 referrals from same browser/device in 24 hours
4. **Volume Spam:** >20 referrals per referrer per month
5. **Self-Referral:** Signup IP matches referrer's last known IP

### 3. Updated ReferralSignupService
**File:** `app/Services/ReferralSignupService.php`

**Changes:**
- Constructor dependency injection for `FraudDetectionService`
- Captures `request()->ip()`, `X-Device-Fingerprint` header, `user_agent()`
- Calls fraud detection before creating referral
- Stores fraud metadata in referral record
- Records metrics for future pattern analysis

**Customer Referrals:**
- Always created even if suspicious (for audit trail)
- Marked with `is_suspicious` flag + reasons
- Status remains 'pending' for admin review

**Business Referrals:**
- If suspicious: `status = 'pending'`, credits NOT awarded
- If legitimate: `status = 'credited'`, credits awarded immediately
- Suspicious referrals require admin verification before credit payout

### 4. Model Updates

#### BusinessReferral
**File:** `app/Models/BusinessReferral.php`

Added to `$fillable`:
```php
'ip_address', 'device_fingerprint', 'user_agent',
'is_suspicious', 'fraud_notes', 'verified_at'
```

Added to `$casts`:
```php
'is_suspicious' => 'boolean',
'verified_at' => 'datetime'
```

#### CustomerReferral
**File:** `app/Models/CustomerReferral.php`

Same fraud detection fields added to `$fillable` and `$casts`.

## Integration Requirements

### Frontend (Client-Side)
Add device fingerprinting to signup/registration pages:

```javascript
// Example using FingerprintJS (https://github.com/fingerprintjs/fingerprintjs)
import FingerprintJS from '@fingerprintjs/fingerprintjs';

const fpPromise = FingerprintJS.load();

fpPromise.then(fp => fp.get()).then(result => {
  const visitorId = result.visitorId;
  
  // Send in signup request headers
  fetch('/api/signup', {
    headers: {
      'X-Device-Fingerprint': visitorId,
      // other headers...
    }
  });
});
```

Alternative simple fingerprint (no library):
```javascript
const fingerprint = btoa(
  navigator.userAgent + 
  screen.width + 
  screen.height + 
  navigator.language +
  new Date().getTimezoneOffset()
);
```

### Admin Dashboard (Filament)
Add resource for reviewing suspicious referrals:

**AdminPanel:**
- List suspicious referrals (is_suspicious=true, verified_at=null)
- Show fraud_notes with detailed reasons
- Actions: "Verify as Legitimate" (awards credits if business referral)
- Actions: "Confirm Fraudulent" (permanently blocks, notifies user)

**Table Filters:**
- Show All / Suspicious Only / Verified
- Filter by referral type (customer/business)

**Metrics Widget:**
- Total suspicious referrals (this month)
- Fraud prevention rate (blocked credits / total potential)
- Top suspicious IPs/devices

## Testing Recommendations

### Unit Tests
```php
// Test fraud detection thresholds
- it_flags_referral_after_3_signups_from_same_ip
- it_flags_referral_after_5_signups_from_same_subnet
- it_flags_referral_after_20_monthly_referrals_per_user
- it_detects_self_referral_with_same_ip

// Test credit withholding
- it_withholds_credits_for_suspicious_business_referrals
- it_awards_credits_after_admin_verification
```

### Manual Testing
1. **Same IP Test:** Create 4 referrals from same IP → 4th should be flagged
2. **Device Test:** Use same browser fingerprint for multiple signups
3. **Volume Test:** Create >20 referrals for one user in a month
4. **Admin Workflow:** Verify a suspicious referral → credits should be awarded

## Cache Configuration

Ensure Redis is configured for production (or use Memcached):

```bash
# .env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

Cache keys used:
- `fraud:ip:{IP}:{date}` - IP counter (expires 24h)
- `fraud:subnet:{subnet}:{date}` - Subnet counter (expires 24h)
- `fraud:device:{fingerprint}:{date}` - Device counter (expires 24h)
- `user:last_ip:{user_id}` - Last known IP for self-referral detection

## Migration Instructions

```bash
# Run new migration
php artisan migrate

# If needed, backfill existing referrals (optional)
php artisan tinker
>>> DB::table('business_referrals')->update(['is_suspicious' => false]);
>>> DB::table('customer_referrals')->update(['is_suspicious' => false]);
```

## Monitoring & Alerts

### Log Monitoring
All suspicious referrals are logged with:
```php
Log::warning('FraudDetectionService: Suspicious referral detected', [
    'ip' => $ipAddress,
    'device' => $deviceFingerprint,
    'reasons' => ['Exceeded max referrals from IP (4 referrals today)']
]);
```

### Admin Notifications
Consider adding:
- Daily digest of suspicious referrals
- Slack/email alert when >10 suspicious referrals in 1 hour (bot attack)
- Webhook to security monitoring service

## Security Notes

1. **IP Spoofing:** Use `request()->ip()` which respects trusted proxies (configure in `TrustProxies` middleware)
2. **Fingerprint Bypassing:** Device fingerprints can be spoofed; combine with IP + behavioral analysis
3. **False Positives:** Shared IPs (offices, cafes) may trigger flags → admin review critical
4. **GDPR Compliance:** IP addresses are PII → anonymize after 90 days or user consent required

## Future Enhancements

1. **Behavioral Analysis:** Track time between referrals, click patterns, form fill speed
2. **Email Verification:** Require email verification before crediting referrals
3. **Phone Verification:** SMS verification for high-value referrals
4. **Machine Learning:** Train model on historical fraud patterns
5. **IP Geolocation:** Flag mismatches between user location and business location
6. **Velocity Checks:** Flag rapid-fire signups (e.g., 5 referrals in 2 minutes)

## Success Metrics

Track these KPIs in admin dashboard:
- **Fraud Detection Rate:** % of referrals flagged as suspicious
- **False Positive Rate:** % of verified-as-legitimate after flagging
- **Credits Saved:** Total credits withheld from suspicious referrals
- **Average Review Time:** Time from flagging to admin verification
- **Top Fraud Vectors:** Most common fraud reasons (IP abuse, device abuse, etc.)

---

**Status:** ✅ Phase 2.2 Complete (Fraud Detection)
**Next:** Phase 2.3 - Audit Trail System
