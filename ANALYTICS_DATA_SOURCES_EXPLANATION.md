# Analytics Data Sources Explanation

## Current Implementation Analysis

### 📊 **What the Analytics Page Reads From:**

#### 1. **IMPRESSIONS (Total Views)**
- **Source:** `business_views` table
- **Query:** `BusinessView::whereIn('business_id', $businessIds)->whereBetween('view_date', ...)->count()`
- **Data Field:** `view_date` column
- **What it counts:** Every row in `business_views` table = 1 impression/view

#### 2. **CLICKS (Total Interactions)**
- **Source:** `business_interactions` table  
- **Query:** `BusinessInteraction::whereIn('business_id', $businessIds)->whereBetween('interaction_date', ...)->count()`
- **Data Field:** `interaction_date` column
- **What it counts:** Every row in `business_interactions` table = 1 click/interaction
- **Interaction Types Tracked:**
  - `call` - Phone number clicks
  - `whatsapp` - WhatsApp button clicks
  - `email` - Email link clicks
  - `website` - Website link clicks
  - `map` - Map/location clicks
  - `directions` - Get directions clicks

#### 3. **CTR (Click-Through Rate)**
- **Calculation:** `(Total Clicks / Total Impressions) × 100`
- **Formula:** `($clicks['total'] / $impressions['total']) * 100`
- **Example:** If 100 views and 5 clicks = 5% CTR

#### 4. **VIEWS BY SOURCE**
- **Source:** `business_views` table
- **Query:** Groups by `referral_source` column
- **Possible Sources:**
  - `yellowbooks` - Internal YellowBooks navigation
  - `google` - Google Search
  - `bing` - Bing Search
  - `facebook` - Facebook
  - `instagram` - Instagram
  - `twitter` - Twitter/X
  - `linkedin` - LinkedIn
  - `direct` - Direct URL visit
  - `other` - Other sources

#### 5. **INTERACTIONS BREAKDOWN**
- **Source:** `business_interactions` table
- **Query:** Groups by `interaction_type` column
- **Shows:**
  - Phone Calls (`call`)
  - WhatsApp (`whatsapp`)
  - Emails (`email`)
  - Website Clicks (`website`)
  - Map Clicks (`map`)

#### 6. **LEADS DATA**
- **Source:** `leads` table
- **Query:** `Lead::whereIn('business_id', $businessIds)->whereBetween('created_at', ...)->count()`
- **Conversion Rate:** `(Total Leads / Total Views) × 100`

---

## ⚠️ **CRITICAL ISSUE: Missing Tracking Implementation**

### **The Problem:**
The analytics page **reads** from the database, but **nothing is writing to it!**

### **What's Missing:**

#### 1. **Views Tracking (Impressions)**
- **Method exists:** `BusinessView::recordView($businessId, $referralSource)`
- **Called from:** `Business::recordView($referralSource)`
- **BUT:** No controllers/routes are calling this method!
- **Where it SHOULD be called:**
  - Business listing page controller
  - Business detail page controller
  - Search results page controller
  - Any public-facing business page

#### 2. **Interactions Tracking (Clicks)**
- **Method exists:** `BusinessInteraction::recordInteraction($businessId, $type, $referralSource)`
- **Called from:** `Business::recordInteraction($type, $referralSource)`
- **BUT:** No controllers/routes are calling this method!
- **Where it SHOULD be called:**
  - When user clicks phone number → `recordInteraction('call')`
  - When user clicks WhatsApp button → `recordInteraction('whatsapp')`
  - When user clicks email → `recordInteraction('email')`
  - When user clicks website link → `recordInteraction('website')`
  - When user clicks map/directions → `recordInteraction('map')` or `recordInteraction('directions')`

---

## 🔧 **What Needs to Be Fixed:**

### **1. Add View Tracking to Public Business Pages**

**Location:** Need to create/update controllers for public business pages

**Example Implementation:**
```php
// In BusinessController or similar
public function show($slug)
{
    $business = Business::where('slug', $slug)->firstOrFail();
    
    // Determine referral source from HTTP referer
    $referralSource = $this->detectReferralSource(request()->header('referer'));
    
    // Record the view
    $business->recordView($referralSource);
    
    return view('business.show', compact('business'));
}

private function detectReferralSource($referer)
{
    if (!$referer) return 'direct';
    
    if (str_contains($referer, 'google.com')) return 'google';
    if (str_contains($referer, 'bing.com')) return 'bing';
    if (str_contains($referer, 'facebook.com')) return 'facebook';
    if (str_contains($referer, 'instagram.com')) return 'instagram';
    if (str_contains($referer, 'twitter.com') || str_contains($referer, 'x.com')) return 'twitter';
    if (str_contains($referer, 'linkedin.com')) return 'linkedin';
    if (str_contains($referer, config('app.url'))) return 'yellowbooks';
    
    return 'other';
}
```

### **2. Add Interaction Tracking to Action Buttons**

**Location:** Frontend JavaScript or API endpoints

**Option A: JavaScript Tracking (Recommended)**
```javascript
// Track phone call click
document.querySelectorAll('a[href^="tel:"]').forEach(link => {
    link.addEventListener('click', function() {
        fetch(`/api/track-interaction/${businessId}`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({type: 'call'})
        });
    });
});

// Track WhatsApp click
document.querySelectorAll('a[href^="https://wa.me"]').forEach(link => {
    link.addEventListener('click', function() {
        fetch(`/api/track-interaction/${businessId}`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({type: 'whatsapp'})
        });
    });
});
```

**Option B: API Endpoint**
```php
// routes/api.php
Route::post('/track-interaction/{business}', [TrackingController::class, 'trackInteraction']);

// TrackingController.php
public function trackInteraction(Business $business, Request $request)
{
    $referralSource = $this->detectReferralSource($request->header('referer'));
    $userId = auth()->id();
    
    $business->recordInteraction(
        type: $request->input('type'), // 'call', 'whatsapp', 'email', etc.
        referralSource: $referralSource,
        userId: $userId
    );
    
    return response()->json(['success' => true]);
}
```

### **3. Fix Referral Source Detection**

**Current Issue:** `BusinessView::recordView()` accepts `$referralSource` but it's always defaulting to `'direct'`

**Solution:** Implement proper referral source detection from:
- HTTP `Referer` header
- UTM parameters (`?utm_source=google`)
- Session data (if user navigated from YellowBooks search)

---

## 📋 **Summary of Data Flow:**

### **Current Flow (BROKEN):**
```
User visits business page → ❌ Nothing happens → Analytics shows 0
User clicks phone number → ❌ Nothing happens → Analytics shows 0
```

### **Expected Flow (SHOULD BE):**
```
User visits business page → Controller calls recordView() → Database row created → Analytics shows +1
User clicks phone number → JS/API calls recordInteraction('call') → Database row created → Analytics shows +1
```

---

## ✅ **What's Working:**
1. ✅ Database tables exist (`business_views`, `business_interactions`)
2. ✅ Model methods exist (`recordView()`, `recordInteraction()`)
3. ✅ Analytics page queries are correct
4. ✅ Data structure is correct

## ❌ **What's NOT Working:**
1. ❌ No controllers calling `recordView()` when pages are viewed
2. ❌ No tracking for interaction clicks (call, WhatsApp, email, etc.)
3. ❌ Referral source always defaults to 'direct'
4. ❌ No API endpoints for frontend tracking

---

## 🎯 **Next Steps to Fix:**

1. **Create/Update Business Controller** to track views
2. **Add JavaScript tracking** for interaction clicks
3. **Create API endpoint** for interaction tracking
4. **Implement referral source detection** from HTTP headers/UTM params
5. **Test the tracking** to ensure data flows correctly

Would you like me to implement these fixes?
