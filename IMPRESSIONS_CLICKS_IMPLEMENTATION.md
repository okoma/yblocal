# Impressions & Clicks Implementation Summary

## ✅ **What Was Created:**

### **1. Database Tables**

#### **`business_impressions` Table**
- **Purpose:** Track when business listings are visible on archive/category/search pages
- **Key Fields:**
  - `business_id` - Foreign key to businesses
  - `page_type` - ENUM: 'archive', 'category', 'search', 'related', 'featured', 'other'
  - `referral_source` - ENUM: 'yellowbooks', 'google', 'bing', 'facebook', etc.
  - `impression_date`, `impression_hour`, `impression_month`, `impression_year` - For time-based queries
  - Location and device tracking fields

#### **`business_clicks` Table**
- **Purpose:** Track clicks to business detail pages (cookie-based, one per person)
- **Key Fields:**
  - `business_id` - Foreign key to businesses
  - `cookie_id` - Unique identifier to prevent duplicate clicks (64 chars)
  - `referral_source` - Where the click came from
  - `source_page_type` - Where click originated ('archive', 'category', 'external', etc.)
  - `click_date`, `click_hour`, `click_month`, `click_year` - For time-based queries
  - **Unique Constraint:** `['business_id', 'cookie_id']` - Ensures one click per person per business
  - Location and device tracking fields

### **2. Models**

#### **`BusinessImpression` Model**
- **Location:** `app/Models/BusinessImpression.php`
- **Key Method:** `recordImpression($businessId, $pageType, $referralSource)`
- **Usage:** Call when a business listing is displayed/visible on a page

#### **`BusinessClick` Model**
- **Location:** `app/Models/BusinessClick.php`
- **Key Method:** `recordClick($businessId, $referralSource, $sourcePageType)`
- **Cookie Management:** 
  - Automatically generates/retrieves cookie ID
  - Checks if click already exists for this person
  - Returns `null` if duplicate (already clicked)
- **Helper Methods:**
  - `detectReferralSource($referer)` - Detects source from HTTP referer
  - `detectSourcePageType($referer)` - Detects page type from URL

### **3. Business Model Updates**

#### **New Relationships:**
```php
public function impressions(): HasMany
public function clicks(): HasMany
```

#### **New Helper Methods:**
```php
public function recordImpression(string $pageType = 'archive', string $referralSource = 'direct')
public function recordClick(string $referralSource = 'direct', ?string $sourcePageType = null)
```

### **4. BusinessViewSummary Updates**

#### **New Fields Added:**
- `total_impressions` - Total impressions count
- `impressions_by_source` - JSON: Breakdown by referral source
- `impressions_by_page_type` - JSON: Breakdown by page type
- `total_clicks` - Total clicks count
- `clicks_by_source` - JSON: Breakdown by referral source
- `clicks_by_page_type` - JSON: Breakdown by page type

#### **Updated `aggregateFor()` Method:**
- Now aggregates impressions and clicks data
- Includes them in summary calculations

### **5. AnalyticsPage Updates**

#### **Updated Methods:**
- `getImpressionsData()` - Now reads from `business_impressions` table
- `getClicksData()` - Now reads from `business_clicks` table
- `getCTRData()` - Calculates CTR from actual clicks/impressions

---

## 🔄 **How It Works:**

### **Impressions Flow:**
1. User visits archive/category/search page
2. Business listings are displayed
3. For each visible listing → Call `BusinessImpression::recordImpression($businessId, $pageType, $referralSource)`
4. Row created in `business_impressions` table
5. Analytics shows total impressions

### **Clicks Flow:**
1. User clicks on business listing (from archive/category/external source)
2. User lands on business detail page
3. Call `BusinessClick::recordClick($businessId, $referralSource, $sourcePageType)`
4. Method checks cookie - if already clicked, returns `null` (no duplicate)
5. If not clicked before → Row created in `business_clicks` table + Cookie set
6. Analytics shows total clicks (unique clicks per person)

### **Views Flow (Already Exists):**
1. User visits business detail page
2. Call `BusinessView::recordView($businessId, $referralSource)`
3. Row created in `business_views` table
4. Analytics shows total views (always counted, not cookie-limited)

---

## 📊 **Data Relationships:**

```
Business
├── views() → business_views table (page visits)
├── impressions() → business_impressions table (listing visibility)
├── clicks() → business_clicks table (unique clicks per person)
├── interactions() → business_interactions table (button clicks)
└── leads() → leads table (inquiries)

All aggregate into → business_view_summaries table
```

---

## 🎯 **Key Differences:**

| Metric | Table | Cookie-Based? | When Recorded |
|--------|-------|---------------|---------------|
| **Impressions** | `business_impressions` | ❌ No | When listing is visible |
| **Clicks** | `business_clicks` | ✅ Yes | When detail page is viewed (once per person) |
| **Views** | `business_views` | ❌ No | When detail page is viewed (always) |
| **Interactions** | `business_interactions` | ❌ No | When contact buttons are clicked |

---

## 📝 **Next Steps (Implementation Required):**

### **1. Track Impressions:**
Call `$business->recordImpression($pageType, $referralSource)` when:
- Business listings are displayed on archive pages
- Business listings are displayed on category pages
- Business listings appear in search results
- Business listings are shown in related/featured sections

### **2. Track Clicks:**
Call `$business->recordClick($referralSource, $sourcePageType)` when:
- User visits business detail page (from any source)
- The method automatically handles cookie checking
- Referral source is detected from HTTP referer header

### **3. Track Views:**
Call `$business->recordView($referralSource)` when:
- User visits business detail page
- This is separate from clicks (views always count)

---

## ✅ **What's Complete:**
- ✅ Database tables created
- ✅ Models created with all methods
- ✅ Business model relationships added
- ✅ Business model helper methods added
- ✅ BusinessViewSummary updated to aggregate impressions/clicks
- ✅ AnalyticsPage updated to read from new tables
- ✅ Analytics view already displays the data correctly

## ⏳ **What's Pending:**
- ⏳ Actual tracking implementation (calling recordImpression/recordClick in controllers/frontend)
- ⏳ Referral source detection from HTTP headers
- ⏳ Cookie management testing
