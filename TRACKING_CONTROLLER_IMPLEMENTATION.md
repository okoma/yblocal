# Tracking Controller Implementation

## ✅ **What Was Created:**

### **1. BusinessController** (`app/Http/Controllers/BusinessController.php`)

A complete controller that handles:
- **Business listing pages** (archive/index)
- **Business detail pages** (show)
- **Category pages**
- **Search results pages**

All with automatic tracking for impressions, clicks, and views.

---

## 📍 **Routes Added:**

```php
// Public Business Routes (with tracking)
Route::prefix('businesses')->name('businesses.')->group(function () {
    Route::get('/', [BusinessController::class, 'index'])->name('index');
    Route::get('/search', [BusinessController::class, 'search'])->name('search');
    Route::get('/category/{categorySlug}', [BusinessController::class, 'category'])->name('category');
    Route::get('/{slug}', [BusinessController::class, 'show'])->name('show');
});
```

**URL Examples:**
- `/businesses` - Main listing page
- `/businesses/search?q=restaurant` - Search results
- `/businesses/category/restaurants` - Category page
- `/businesses/my-business-slug` - Business detail page

---

## 🔄 **How Tracking Works:**

### **1. Listing Pages (index, category, search):**
```php
// For each visible business on the page:
$business->recordImpression($pageType, $referralSource);
```
- **When:** Business listing is displayed/visible
- **What:** Records impression in `business_impressions` table
- **Page Types:** 'archive', 'category', 'search', 'related', 'featured'

### **2. Detail Page (show):**
```php
// When user visits business detail page:
$business->recordClick($referralSource, $sourcePageType);  // Cookie-based (one per person)
$business->recordView($referralSource);                     // Always counts
```
- **Click:** Cookie-based, only records once per person (until cookie expires - 30 days)
- **View:** Always records, even if user visits multiple times

---

## 🎯 **Automatic Detection:**

### **Referral Source Detection:**
Automatically detects where traffic came from:
- `'yellowbooks'` - Internal navigation
- `'google'` - Google search
- `'bing'` - Bing search
- `'facebook'` - Facebook
- `'instagram'` - Instagram
- `'twitter'` - Twitter/X
- `'linkedin'` - LinkedIn
- `'direct'` - Direct URL visit
- `'other'` - Other sources

### **Source Page Type Detection (for clicks):**
Detects where the click originated from:
- `'archive'` - From listing/archive page
- `'category'` - From category page
- `'search'` - From search results
- `'related'` - From related businesses
- `'featured'` - From featured section
- `'external'` - From external source (Google, etc.)
- `'other'` - Other internal pages

---

## ✅ **Features Implemented:**

### **1. Proper Model Relationships:**
- ✅ Uses `businessType()` relationship (BelongsTo BusinessType)
- ✅ Uses `categories()` relationship (BelongsToMany Category)
- ✅ Uses `stateLocation()` and `cityLocation()` relationships (BelongsTo Location)
- ✅ Eager loading for performance: `with(['businessType', 'stateLocation', 'cityLocation', 'categories'])`

### **2. Advanced Filtering:**
- ✅ Filter by Business Type (`?business_type=restaurants`)
- ✅ Filter by State Location (`?state=lagos`)
- ✅ Filter by City Location (`?city=ikeja`)
- ✅ Filter by Category (`?category=fast-food`)
- ✅ Combined filters work together

### **3. Enhanced Search:**
- ✅ Searches in: business name, description, city, state, area
- ✅ Searches in related: business type name, category names
- ✅ Can combine search with filters

### **4. Location Support:**
- ✅ Supports both direct fields (`state`, `city`) and relationships (`stateLocation`, `cityLocation`)
- ✅ Filters work with either slug or name matching

### **3. Views:**
The controller returns views that don't exist yet:
- `businesses.index`
- `businesses.show`
- `businesses.category`
- `businesses.search`

**Create these views or update the return statements to match your existing views.**

---

## 🛡️ **Error Handling:**

All tracking calls are wrapped in try-catch blocks:
```php
try {
    $business->recordImpression($pageType, $referralSource);
} catch (\Exception $e) {
    \Log::warning("Failed to record impression: " . $e->getMessage());
}
```

**Why:** Tracking failures won't break the page - errors are logged but the page still loads.

---

## 📊 **Data Flow:**

```
User visits /businesses
    ↓
BusinessController@index
    ↓
For each visible business:
    → recordImpression('archive', 'direct')
    → Creates row in business_impressions table

User clicks on business
    ↓
User visits /businesses/my-business-slug
    ↓
BusinessController@show
    ↓
    → recordClick('yellowbooks', 'archive')  // Cookie-based
    → recordView('yellowbooks')               // Always counts
    → Creates rows in business_clicks and business_views tables
```

---

## ✅ **What's Complete:**
- ✅ Controller created with all methods
- ✅ Routes added to `web.php`
- ✅ Automatic referral source detection
- ✅ Automatic page type detection
- ✅ Error handling for tracking failures
- ✅ Cookie-based click tracking
- ✅ Impression tracking for listings
- ✅ View tracking for detail pages

## ⏳ **What You Need to Do:**
1. **Customize business queries** in each method
2. **Create/update views** for the pages
3. **Update category relationship** if different
4. **Test the routes** to ensure they work
5. **Run migrations** to create the tables:
   ```bash
   php artisan migrate
   ```

---

## 🧪 **Testing:**

### **Test Impressions:**
1. Visit `/businesses`
2. Check `business_impressions` table - should have rows for each visible business

### **Test Clicks:**
1. Visit `/businesses/my-business-slug`
2. Check `business_clicks` table - should have 1 row
3. Visit again - should NOT create duplicate (cookie prevents it)
4. Clear cookies and visit again - should create new row

### **Test Views:**
1. Visit `/businesses/my-business-slug`
2. Check `business_views` table - should have row
3. Visit again - should create another row (always counts)

---

## 📚 **Related Files:**
- `app/Models/BusinessImpression.php` - Impression model
- `app/Models/BusinessClick.php` - Click model
- `app/Models/BusinessView.php` - View model
- `app/Models/Business.php` - Business model with helper methods
- `app/Filament/Business/Pages/AnalyticsPage.php` - Analytics dashboard
