# Notification Preferences - Fixes Applied

## ✅ What Was Fixed

### 1. Database Migration Created
**File:** `database/migrations/2026_01_28_000002_add_missing_notification_preferences.php`

**Added Columns:**
- **Business Email:**
  - `notify_claim_submitted` (default: true)
  - `notify_claim_approved` (default: true)
  - `notify_claim_rejected` (default: true)
  - `notify_new_quote_requests` (default: true)
  - `notify_business_reported` (default: true)

- **Business Telegram:**
  - `notify_claim_submitted_telegram` (default: false)
  - `notify_claim_approved_telegram` (default: false)
  - `notify_claim_rejected_telegram` (default: false)
  - `notify_new_quote_requests_telegram` (default: false)
  - `notify_business_reported_telegram` (default: false)

- **Customer:**
  - `notify_quote_responses` (default: true) - Email
  - `notify_quote_updates` (default: true) - Email
  - `notify_quote_responses_app` (default: true) - In-app
  - `notify_quote_updates_app` (default: true) - In-app

---

### 2. UserPreference Model Updated
**File:** `app/Models/UserPreference.php`

- ✅ Added all new fields to `$fillable` array
- ✅ Added all new fields to `$casts` array (as boolean)
- ✅ Updated `getForUser()` defaults for all new preferences

---

### 3. Business Account Preferences Form Updated
**File:** `app/Filament/Business/Pages/AccountPreferences.php`

**Added Email Notification Toggles:**
- Claim Submitted
- Claim Approved
- Claim Rejected
- New Quote Requests
- Business Reported

**Added Telegram Notification Toggles:**
- Claim Submitted (Telegram)
- Claim Approved (Telegram)
- Claim Rejected (Telegram)
- New Quote Requests (Telegram)
- Business Reported (Telegram)

**Updated:**
- `fillForm()` method to load new preferences
- Form schema to include new toggles

---

### 4. Customer Notification Preferences Form Updated
**File:** `app/Filament/Customer/Pages/NotificationPreferences.php`

**Added Email Notification Toggles:**
- Quote Responses
- Quote Updates

**Added In-App Notification Toggles:**
- Quote Responses (In-App)
- Quote Updates (In-App)

**Updated:**
- `mount()` method to load new preferences
- Form schema to include new toggles
- "Enable All" / "Disable All" actions to include quote preferences
- Info section to explain quote notifications

---

### 5. Notification Classes Updated
**Files Updated:**
- `app/Notifications/ClaimSubmittedNotification.php`
- `app/Notifications/ClaimApprovedNotification.php`
- `app/Notifications/ClaimRejectedNotification.php`
- `app/Notifications/BusinessReportedNotification.php`

**Changes:**
- Updated `via()` method to check preferences before sending email
- Database notifications always sent (for in-app display)
- Email only sent if preference is enabled

---

### 6. Quote Notification Code Updated
**Files Updated:**
- `app/Filament/Customer/Resources/QuoteRequestResource/Pages/CreateQuoteRequest.php`
- `app/Filament/Business/Pages/AvailableQuoteRequests.php`

**Changes:**
- Added preference checks before sending quote notifications
- Business owners only receive quote request notifications if `notify_new_quote_requests` is enabled
- Customers only receive quote response notifications if `notify_quote_responses` is enabled

---

## 📋 Next Steps

1. **Run Migration:**
   ```bash
   php artisan migrate
   ```

2. **Test:**
   - Business: Check Account Preferences page - all new toggles should appear
   - Customer: Check Notification Preferences page - quote toggles should appear
   - Test notification sending with preferences enabled/disabled

---

## 🎯 Summary

**Total Missing Preferences Fixed:** 14
- Business Email: 5
- Business Telegram: 5
- Customer Email: 2
- Customer In-App: 2

**All notification types now have user-controllable preferences!** ✅
