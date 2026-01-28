# Notification Preferences Audit Report

## 📋 Summary

This audit compares the notification types that exist in the system with the preferences available in Business and Customer panels.

---

## ✅ Business Account Preferences - What's Available

### Email Notifications
- ✅ `notify_new_leads` - New Leads
- ✅ `notify_new_reviews` - New Reviews  
- ✅ `notify_review_replies` - Review Replies
- ✅ `notify_verifications` - Verification Updates
- ✅ `notify_premium_expiring` - Premium Expiring
- ✅ `notify_campaign_updates` - Campaign Updates

### Telegram Notifications
- ✅ `notify_new_leads_telegram`
- ✅ `notify_new_reviews_telegram`
- ✅ `notify_review_replies_telegram`
- ✅ `notify_verifications_telegram`
- ✅ `notify_premium_expiring_telegram`
- ✅ `notify_campaign_updates_telegram`

### WhatsApp Notifications
- ✅ `notify_new_leads_whatsapp`
- ✅ `notify_new_reviews_whatsapp`

---

## ❌ Business Account Preferences - What's MISSING

### 1. Claim Notifications
**Notification Types:**
- `claim_submitted` - When a business claim is submitted
- `claim_approved` - When a business claim is approved
- `claim_rejected` - When a business claim is rejected

**Missing Preferences:**
- `notify_claim_submitted` (email)
- `notify_claim_approved` (email)
- `notify_claim_rejected` (email)
- `notify_claim_submitted_telegram`
- `notify_claim_approved_telegram`
- `notify_claim_rejected_telegram`

**Impact:** Users receive claim notifications but cannot control them via preferences.

---

### 2. Quote System Notifications (Business Side)
**Notification Types:**
- `new_quote_request` - When a new quote request matches their business
- `new_quote_response` - When they submit a quote (confirmation)

**Missing Preferences:**
- `notify_new_quote_requests` (email)
- `notify_new_quote_requests_telegram`
- `notify_quote_responses` (email) - Optional, for confirmation

**Impact:** Businesses receive quote request notifications but cannot control them.

---

### 3. Business Reported Notifications
**Notification Types:**
- `business_reported` - When their business is reported by a user

**Missing Preferences:**
- `notify_business_reported` (email)
- `notify_business_reported_telegram`

**Impact:** Business owners receive report notifications but cannot control them.

---

## ✅ Customer Notification Preferences - What's Available

### Email Notifications
- ✅ `notify_review_reply_received` - Review Replies
- ✅ `notify_inquiry_response_received` - Inquiry Responses
- ✅ `notify_saved_business_updates` - Business Updates
- ✅ `notify_promotions_customer` - Special Offers & Promotions
- ✅ `notify_newsletter_customer` - Newsletter & Platform Updates

### In-App Notifications
- ✅ `notify_review_reply_app` - Review Replies
- ✅ `notify_inquiry_response_app` - Inquiry Responses
- ✅ `notify_saved_business_updates_app` - Business Updates
- ✅ `notify_promotions_app` - Promotions

---

## ❌ Customer Notification Preferences - What's MISSING

### 1. Quote System Notifications (Customer Side)
**Notification Types:**
- `new_quote_response` - When a business submits a quote for their request
- `quote_shortlisted` - When their quote is shortlisted
- `quote_accepted` - When their quote is accepted
- `quote_rejected` - When their quote is rejected

**Missing Preferences:**
- `notify_quote_responses` (email) - When businesses submit quotes
- `notify_quote_updates` (email) - When quotes are shortlisted/accepted/rejected
- `notify_quote_responses_app` (in-app)
- `notify_quote_updates_app` (in-app)

**Impact:** Customers receive quote notifications but cannot control them.

---

## 📊 Comparison Table

| Notification Type | Business Email | Business Telegram | Customer Email | Customer In-App | Status |
|------------------|----------------|-------------------|----------------|-----------------|--------|
| **Leads** | ✅ | ✅ | N/A | N/A | ✅ Complete |
| **Reviews** | ✅ | ✅ | ✅ | ✅ | ✅ Complete |
| **Verifications** | ✅ | ✅ | N/A | N/A | ✅ Complete |
| **Premium Expiring** | ✅ | ✅ | N/A | N/A | ✅ Complete |
| **Campaign Updates** | ✅ | ✅ | N/A | N/A | ✅ Complete |
| **Claim Submitted** | ❌ | ❌ | N/A | N/A | ❌ Missing |
| **Claim Approved** | ❌ | ❌ | N/A | N/A | ❌ Missing |
| **Claim Rejected** | ❌ | ❌ | N/A | N/A | ❌ Missing |
| **New Quote Requests** | ❌ | ❌ | N/A | N/A | ❌ Missing |
| **Quote Responses** | ❌ | ❌ | ❌ | ❌ | ❌ Missing |
| **Quote Updates** | N/A | N/A | ❌ | ❌ | ❌ Missing |
| **Business Reported** | ❌ | ❌ | N/A | N/A | ❌ Missing |

---

## 🔧 Recommended Actions

### Priority 1: High Impact
1. **Add Quote Preferences (Both Panels)**
   - Business: `notify_new_quote_requests` (email/telegram)
   - Customer: `notify_quote_responses`, `notify_quote_updates` (email/in-app)

### Priority 2: Medium Impact
2. **Add Claim Preferences (Business)**
   - `notify_claim_submitted`, `notify_claim_approved`, `notify_claim_rejected` (email/telegram)

### Priority 3: Low Impact (Optional)
3. **Add Business Reported Preference (Business)**
   - `notify_business_reported` (email/telegram) - Usually want to know about this

---

## 📝 Implementation Notes

1. **Database Migration Required:**
   - Add new preference columns to `user_preferences` table
   - Update `UserPreference` model `$fillable` and `$casts`
   - Update default values in `getForUser()` method

2. **UI Updates Required:**
   - Business: Add toggles in `AccountPreferences.php` form
   - Customer: Add toggles in `NotificationPreferences.php` form

3. **Notification Classes:**
   - Most notification classes already check preferences
   - Need to add preference checks for quote notifications
   - Need to add preference checks for claim notifications

---

## 🎯 Quick Fix Summary

**Business Panel Missing:**
- Claim notifications (3 types)
- Quote request notifications (1 type)
- Business reported notifications (1 type)

**Customer Panel Missing:**
- Quote response notifications (1 type)
- Quote update notifications (3 types: shortlisted, accepted, rejected)

**Total Missing:** 9 preference toggles across both panels
