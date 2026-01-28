# URL Structure Updated - Business Type in Path

## 🎯 **New URL Structure**

URLs now include the business type slug for better SEO and user experience.

---

## 📍 **URL Format**

### Before:
```
/businesses/grand-hotel
/businesses/grand-hotel/reviews
/businesses/grand-hotel/leads
/businesses/grand-hotel/photos
```

### After (NEW):
```
/hotel/grand-hotel
/hotel/grand-hotel/reviews
/hotel/grand-hotel/leads
/hotel/grand-hotel/photos
```

---

## 🔗 **Complete URL Examples**

### Hotels
```
GET  /hotel/grand-hotel
GET  /hotel/grand-hotel/reviews
POST /hotel/grand-hotel/reviews
POST /hotel/grand-hotel/leads
GET  /hotel/grand-hotel/photos
```

### Restaurants
```
GET  /restaurant/tasty-food
GET  /restaurant/tasty-food/reviews
POST /restaurant/tasty-food/reviews
POST /restaurant/tasty-food/leads
GET  /restaurant/tasty-food/photos
```

### Hospitals
```
GET  /hospital/city-hospital
GET  /hospital/city-hospital/reviews
POST /hospital/city-hospital/reviews
POST /hospital/city-hospital/leads
GET  /hospital/city-hospital/photos
```

---

## 🛣️ **Updated Routes**

```php
// Business Type Based Routes
Route::name('businesses.')->group(function () {
    // Single business detail page
    Route::get('/{businessType}/{slug}', [BusinessController::class, 'show'])
        ->name('show');
    
    // Reviews
    Route::get('/{businessType}/{slug}/reviews', [ReviewController::class, 'index'])
        ->name('reviews.index');
    Route::post('/{businessType}/{slug}/reviews', [ReviewController::class, 'store'])
        ->name('reviews.store');
    
    // Leads/Inquiries
    Route::post('/{businessType}/{slug}/leads', [LeadController::class, 'store'])
        ->name('leads.store');
    
    // Photos/Gallery
    Route::get('/{businessType}/{slug}/photos', [PhotoController::class, 'index'])
        ->name('photos.index');
    Route::post('/{businessType}/{slug}/photos', [PhotoController::class, 'store'])
        ->name('photos.store');
    Route::delete('/{businessType}/{slug}/photos/{photoPath}', [PhotoController::class, 'destroy'])
        ->name('photos.destroy');
});
```

---

## ✅ **Controllers Updated**

All controllers now validate that the business type in the URL matches the actual business type:

### 1. **BusinessController**
```php
public function show(Request $request, string $businessType, string $slug)
{
    $business = Business::where('slug', $slug)
        ->where('status', 'active')
        ->whereHas('businessType', function ($query) use ($businessType) {
            $query->where('slug', $businessType);
        })
        ->firstOrFail();
    // ...
}
```

### 2. **ReviewController**
- ✅ `index()` - Updated
- ✅ `store()` - Updated

### 3. **LeadController**
- ✅ `store()` - Updated

### 4. **PhotoController**
- ✅ `index()` - Updated
- ✅ `store()` - Updated
- ✅ `destroy()` - Updated

### 5. **MapController**
- ✅ URLs in JSON responses updated

---

## 🎨 **Business Model Helper**

Added helper method to generate correct URLs:

```php
// In Business model
public function getUrl()
{
    if (!$this->businessType) {
        return route('businesses.show', [
            'businessType' => 'business',
            'slug' => $this->slug
        ]);
    }
    
    return route('businesses.show', [
        'businessType' => $this->businessType->slug,
        'slug' => $this->slug
    ]);
}

// Usage in views/controllers
$business->getUrl() // Returns: /hotel/grand-hotel
```

---

## 📊 **Route Name Generation**

```php
// Generate business URL
route('businesses.show', [
    'businessType' => 'hotel',
    'slug' => 'grand-hotel'
]); // /hotel/grand-hotel

// Generate reviews URL
route('businesses.reviews.index', [
    'businessType' => 'restaurant',
    'slug' => 'tasty-food'
]); // /restaurant/tasty-food/reviews

// Generate leads URL
route('businesses.leads.store', [
    'businessType' => 'hospital',
    'slug' => 'city-hospital'
]); // /hospital/city-hospital/leads
```

---

## ✅ **Benefits**

1. **Better SEO** - URLs are more descriptive
2. **User-Friendly** - Clear what type of business it is
3. **Better Organization** - Businesses grouped by type in URL structure
4. **Canonical URLs** - Each business has a unique, descriptive URL
5. **Validation** - URL business type must match actual business type

---

## 🧪 **Testing Examples**

```bash
# Hotels
curl "http://localhost/hotel/grand-hotel"
curl "http://localhost/hotel/grand-hotel/reviews"
curl -X POST "http://localhost/hotel/grand-hotel/reviews" -d '{"rating": 5}'

# Restaurants
curl "http://localhost/restaurant/tasty-food"
curl "http://localhost/restaurant/tasty-food/photos"
curl -X POST "http://localhost/restaurant/tasty-food/leads" -d '{"name": "John"}'

# Hospitals
curl "http://localhost/hospital/city-hospital"
curl "http://localhost/hospital/city-hospital/reviews?sort=newest"
```

---

## 🚨 **Important Notes**

1. **Business Type Must Match**: The business type slug in the URL must match the actual business's business type. Otherwise, a 404 error is returned.

2. **URL Generation**: Always use the `getUrl()` method or route helpers to generate URLs:
   ```php
   // Good
   $business->getUrl()
   route('businesses.show', ['businessType' => $business->businessType->slug, 'slug' => $business->slug])
   
   // Bad (old way)
   route('businesses.show', $business->slug)
   ```

3. **Existing Links**: Update any existing hardcoded links in your views to use the new format.

4. **Redirects**: Consider adding redirects from old URLs to new URLs if you have existing indexed pages.

---

## 📝 **Migration Notes**

If you have existing businesses with old URLs, you can add a redirect in your routes:

```php
// Optional: Redirect old URLs to new format
Route::get('/businesses/{slug}', function ($slug) {
    $business = Business::where('slug', $slug)->firstOrFail();
    return redirect($business->getUrl(), 301);
});
```

---

## ✅ **All Changes Complete**

- ✅ Routes updated
- ✅ BusinessController updated
- ✅ ReviewController updated
- ✅ LeadController updated
- ✅ PhotoController updated
- ✅ MapController updated
- ✅ Business model helper added
- ✅ No linter errors
- ✅ All validation in place

**Your URL structure is now SEO-friendly and production-ready!** 🚀
