# Analytics Database Structure - Complete Analysis

## 📊 **Database Tables Overview**

### **1. `business_views` Table**
**Purpose:** Tracks every time someone views/visits a business page

**Key Fields:**
- `id` - Primary key
- `business_id` - Foreign key to `businesses` table (added later, nullable)
- `business_branch_id` - Foreign key to `business_branches` table (nullable, legacy)
- `referral_source` - ENUM: Where the visitor came from
  - Values: `yellowbooks`, `google`, `bing`, `facebook`, `instagram`, `twitter`, `linkedin`, `direct`, `other`
- `country`, `country_code`, `region`, `city` - Location data (IP-based)
- `ip_address` - Visitor's IP address
- `user_agent` - Browser/device info
- `device_type` - `mobile`, `desktop`, or `tablet`
- `viewed_at` - Timestamp of when view occurred
- `view_date` - Date (for daily grouping)
- `view_hour` - Hour (00-23) for hourly stats
- `view_month` - YYYY-MM for monthly stats
- `view_year` - YYYY for yearly stats

**What it represents:**
- **Each row = 1 page view/impression**
- When someone visits a business listing page, a row should be created here
- Used to calculate: Total Views, Views by Source, Views by Date

**Relationship:**
- `Business` model: `hasMany(BusinessView::class)` → `$business->views()`

---

### **2. `business_interactions` Table**
**Purpose:** Tracks every time someone clicks/interacts with business contact buttons

**Key Fields:**
- `id` - Primary key
- `business_id` - Foreign key to `businesses` table (added later, nullable)
- `business_branch_id` - Foreign key to `business_branches` table (nullable, legacy)
- `user_id` - Foreign key to `users` table (nullable - can be guest)
- `interaction_type` - ENUM: Type of interaction
  - Values: `call`, `whatsapp`, `email`, `website`, `map`, `directions`
- `referral_source` - ENUM: Same as views (where they came from)
- `country`, `country_code`, `region`, `city` - Location data
- `ip_address` - Visitor's IP address
- `user_agent` - Browser/device info
- `device_type` - `mobile`, `desktop`, or `tablet`
- `interacted_at` - Timestamp of when interaction occurred
- `interaction_date` - Date (for daily grouping)
- `interaction_hour` - Hour (00-23)
- `interaction_month` - YYYY-MM
- `interaction_year` - YYYY

**What it represents:**
- **Each row = 1 click/interaction**
- When someone clicks phone, WhatsApp, email, website, or map button, a row should be created here
- Used to calculate: Total Interactions, Calls, WhatsApp, Emails, Website Clicks, Map Clicks

**Relationship:**
- `Business` model: `hasMany(BusinessInteraction::class)` → `$business->interactions()`

---

### **3. `leads` Table**
**Purpose:** Stores customer inquiries/leads submitted through forms

**Key Fields:**
- `id` - Primary key
- `business_id` - Foreign key to `businesses` table (added later, nullable)
- `business_branch_id` - Foreign key to `business_branches` table (nullable, legacy)
- `user_id` - Foreign key to `users` table (nullable - can be guest)
- `client_name` - Customer's name
- `email` - Customer's email
- `phone` - Customer's phone (nullable)
- `whatsapp` - Customer's WhatsApp (nullable)
- `lead_button_text` - Which button/form was used (e.g., "Book Now", "Get Quote")
- `custom_fields` - JSON field for additional form data
- `status` - ENUM: `new`, `contacted`, `qualified`, `converted`, `lost`
- `is_replied` - Boolean: Has business replied?
- `replied_at` - Timestamp when replied
- `reply_message` - Reply message text
- `notes` - Internal notes
- `created_at`, `updated_at`, `deleted_at` (soft deletes)

**What it represents:**
- **Each row = 1 lead/inquiry**
- When someone submits a contact form, inquiry form, or booking form, a row is created here
- Used to calculate: Total Leads, Leads by Status, Conversion Rate

**Relationship:**
- `Business` model: `hasMany(Lead::class)` → `$business->leads()`

---

## 🔗 **Relationships**

### **Business Model Relationships:**
```php
// Views relationship
public function views(): HasMany
{
    return $this->hasMany(BusinessView::class);
}

// Interactions relationship
public function interactions(): HasMany
{
    return $this->hasMany(BusinessInteraction::class);
}

// Leads relationship
public function leads(): HasMany
{
    return $this->hasMany(Lead::class);
}
```

---

## 📈 **What Analytics Page Actually Reads:**

### **1. Total Views (Impressions)**
- **Source:** `business_views` table
- **Query:** Count rows where `business_id` matches and `view_date` is in date range
- **Formula:** `BusinessView::whereIn('business_id', $ids)->whereBetween('view_date', ...)->count()`
- **What it means:** Total number of times business page was viewed

### **2. Total Interactions (Clicks)**
- **Source:** `business_interactions` table
- **Query:** Count rows where `business_id` matches and `interaction_date` is in date range
- **Formula:** `BusinessInteraction::whereIn('business_id', $ids)->whereBetween('interaction_date', ...)->count()`
- **What it means:** Total number of clicks on contact buttons (call, WhatsApp, email, website, map)

### **3. CTR (Click-Through Rate)**
- **Source:** Calculated from Views and Interactions
- **Formula:** `(Total Interactions ÷ Total Views) × 100`
- **What it means:** Percentage of viewers who clicked a contact button

### **4. Views by Source**
- **Source:** `business_views.referral_source` column
- **Query:** Group by `referral_source` and count
- **Formula:** `BusinessView::groupBy('referral_source')->count()`
- **What it means:** Breakdown of where visitors came from (Google, Facebook, direct, etc.)

### **5. Interactions Breakdown**
- **Source:** `business_interactions.interaction_type` column
- **Query:** Group by `interaction_type` and count
- **Formula:** `BusinessInteraction::groupBy('interaction_type')->count()`
- **What it means:** Breakdown of interaction types (calls, WhatsApp, emails, etc.)

### **6. Total Leads**
- **Source:** `leads` table
- **Query:** Count rows where `business_id` matches and `created_at` is in date range
- **Formula:** `Lead::whereIn('business_id', $ids)->whereBetween('created_at', ...)->count()`
- **What it means:** Total number of inquiries/leads submitted

### **7. Conversion Rate**
- **Source:** Calculated from Leads and Views
- **Formula:** `(Total Leads ÷ Total Views) × 100`
- **What it means:** Percentage of viewers who submitted a lead/inquiry

---

## ⚠️ **Important Notes:**

1. **No Separate "Impressions" Table:**
   - Impressions = Total Views from `business_views` table
   - Not a separate entity, just a different name for the same data

2. **No Separate "Clicks" Table:**
   - Clicks = Total Interactions from `business_interactions` table
   - Not a separate entity, just a different name for the same data

3. **Both Tables Have `business_id`:**
   - Added via migrations: `2026_01_07_101230_add_business_id_to_business_views.php`
   - Added via migrations: `2026_01_07_101351_add_business_id_to_business_interactions.php`
   - `business_branch_id` is still there but nullable (legacy support)

4. **Referral Source Detection:**
   - Currently defaults to `'direct'` in `BusinessView::recordView()`
   - Should be detected from HTTP `Referer` header or UTM parameters

5. **Tracking Methods Exist But Not Called:**
   - `BusinessView::recordView($businessId, $referralSource)` - exists but not called
   - `BusinessInteraction::recordInteraction($businessId, $type, $referralSource)` - exists but not called
   - Need to implement actual tracking in controllers/frontend

---

## 📋 **Summary:**

| Metric | Database Table | Field Used | What It Counts |
|--------|---------------|------------|----------------|
| **Total Views** | `business_views` | `view_date` | Each row = 1 page view |
| **Total Interactions** | `business_interactions` | `interaction_date` | Each row = 1 click/action |
| **CTR** | Calculated | Views ÷ Interactions | Percentage |
| **Views by Source** | `business_views` | `referral_source` | Grouped by source |
| **Interactions Breakdown** | `business_interactions` | `interaction_type` | Grouped by type |
| **Total Leads** | `leads` | `created_at` | Each row = 1 inquiry |
| **Conversion Rate** | Calculated | Leads ÷ Views | Percentage |

---

## ✅ **What's Working:**
- ✅ Database tables exist and are properly structured
- ✅ Models have correct relationships
- ✅ Analytics queries are correct
- ✅ Data structure supports all metrics

## ❌ **What's Missing:**
- ❌ No code that actually creates rows in `business_views` table
- ❌ No code that actually creates rows in `business_interactions` table
- ❌ Referral source always defaults to 'direct' (not detected)
- ❌ No tracking implementation in controllers/frontend
