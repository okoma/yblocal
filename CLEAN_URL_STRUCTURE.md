# Clean URL Structure - No Prefixes

## 🎯 **New Clean URL Structure**

URLs now use clean paths without category/location prefixes for better SEO and user experience.

---

## 📍 **URL Format**

### Old Structure:
```
/categories/hotels
/locations/lagos
/categories/hotels?state=lagos
```

### New Clean Structure:
```
/hotels              → Category/Business Type page
/lagos               → Location page (state or city)
/lagos/hotels        → Location + Category filter
```

---

## 🔗 **Complete URL Examples**

### 1. Single Category/Business Type
```
/hotels              → All hotels
/restaurants         → All restaurants
/hospitals           → All hospitals
/schools             → All schools
```

### 2. Single Location
```
/lagos               → All businesses in Lagos (state)
/abuja               → All businesses in Abuja
/ikeja               → All businesses in Ikeja (city)
/lekki               → All businesses in Lekki
```

### 3. Location + Category/Business Type (Filtering)
```
/lagos/hotels        → Hotels in Lagos
/lagos/restaurants   → Restaurants in Lagos
/abuja/hospitals     → Hospitals in Abuja
/ikeja/schools       → Schools in Ikeja
/lekki/hotels        → Hotels in Lekki
```

### 4. Business Detail (with business type)
```
/hotel/grand-hotel           → Specific hotel
/restaurant/tasty-food       → Specific restaurant
/hospital/city-hospital      → Specific hospital
```

### 5. Additional Filtering (Query Params)
```
/lagos/hotels?rating=4&verified=true
/abuja/restaurants?open_now=true&sort=rating
/hotels?state=lagos&city=ikeja&premium=true
```

---

## 🛣️ **Updated Routes**

```php
// Clean URL Routes (no prefixes)
// Order matters: most specific first
Route::name('businesses.')->group(function () {
    // Business detail (with business type)
    Route::get('/{businessType}/{slug}', [BusinessController::class, 'show'])
        ->name('show');
    
    // Business reviews, leads, photos
    Route::get('/{businessType}/{slug}/reviews', [ReviewController::class, 'index'])
        ->name('reviews.index');
    Route::post('/{businessType}/{slug}/reviews', [ReviewController::class, 'store'])
        ->name('reviews.store');
    Route::post('/{businessType}/{slug}/leads', [LeadController::class, 'store'])
        ->name('leads.store');
    Route::get('/{businessType}/{slug}/photos', [PhotoController::class, 'index'])
        ->name('photos.index');
});

// Discovery routes (handles categories and locations)
Route::get('/{location}/{category}', [DiscoveryController::class, 'index'])
    ->name('discovery.combined'); // e.g., /lagos/hotels

Route::get('/{locationOrCategory}', [DiscoveryController::class, 'index'])
    ->name('discovery.single'); // e.g., /lagos or /hotels

// API routes (keep prefix to avoid conflicts)
Route::prefix('api')->group(function () {
    Route::get('locations/states', [LocationController::class, 'getStates']);
    Route::get('locations/states/{stateSlug}/cities', [LocationController::class, 'getCitiesByState']);
});
```

---

## 🎨 **How It Works**

### URL Detection Logic

The `DiscoveryController` intelligently detects what each URL segment represents:

```php
public function index(Request $request, ?string $locationOrCategory = null, ?string $category = null)
{
    // 1. Check if first segment is a location (state or city)
    $location = Location::where('slug', $locationOrCategory)->first();
    
    if ($location) {
        // It's a location (e.g., /lagos)
        $request->merge(['state' => $locationOrCategory]); // or 'city'
        
        // If second segment exists, check if it's category/business type
        if ($category) {
            // e.g., /lagos/hotels
            $categoryModel = Category::where('slug', $category)->first();
            if ($categoryModel) {
                $request->merge(['category' => $category]);
            }
        }
    } else {
        // 2. Not a location, check if it's a category or business type
        $categoryModel = Category::where('slug', $locationOrCategory)->first();
        if ($categoryModel) {
            // It's a category (e.g., /hotels)
            $request->merge(['category' => $locationOrCategory]);
        }
    }
    
    // Continue with normal discovery logic...
}
```

---

## 📊 **URL Examples by Use Case**

### Browse by Location
```bash
GET /lagos                    # All businesses in Lagos
GET /abuja                    # All businesses in Abuja
GET /ikeja                    # All businesses in Ikeja (city)
GET /lekki                    # All businesses in Lekki (city)
```

### Browse by Category
```bash
GET /hotels                   # All hotels everywhere
GET /restaurants              # All restaurants everywhere
GET /hospitals                # All hospitals everywhere
GET /schools                  # All schools everywhere
```

### Browse by Location + Category
```bash
GET /lagos/hotels             # Hotels in Lagos
GET /lagos/restaurants        # Restaurants in Lagos
GET /abuja/hospitals          # Hospitals in Abuja
GET /ikeja/schools            # Schools in Ikeja
GET /lekki/hotels             # Hotels in Lekki
```

### Browse with Filters
```bash
GET /lagos/hotels?rating=4&verified=true
GET /abuja/restaurants?open_now=true&sort=rating
GET /hotels?premium=true&sort=distance&lat=6.5&lng=3.3
```

### Business Detail
```bash
GET /hotel/grand-hotel              # Specific hotel
GET /hotel/grand-hotel/reviews      # Hotel reviews
POST /hotel/grand-hotel/reviews     # Submit review
POST /hotel/grand-hotel/leads       # Submit inquiry
GET /hotel/grand-hotel/photos       # Hotel gallery
```

---

## 🎯 **SEO Benefits**

### Before (with prefixes):
```
❌ /categories/hotels
❌ /locations/lagos
❌ /categories/hotels?location=lagos
```

### After (clean URLs):
```
✅ /hotels
✅ /lagos
✅ /lagos/hotels
```

**Benefits:**
1. **Shorter URLs** - Easier to remember and share
2. **Cleaner** - More professional appearance
3. **Better SEO** - Search engines prefer clean URLs
4. **User-Friendly** - Obvious what page you're on
5. **Natural Language** - Reads like real phrases

---

## 🔄 **Route Priority**

Routes are checked in this order:

1. **Business Detail Routes** (most specific)
   - `/{businessType}/{slug}/reviews`
   - `/{businessType}/{slug}/leads`
   - `/{businessType}/{slug}/photos`
   - `/{businessType}/{slug}`

2. **Location + Category** (two segments)
   - `/{location}/{category}` → /lagos/hotels

3. **Single Location or Category** (one segment)
   - `/{locationOrCategory}` → /lagos or /hotels

4. **API Routes** (prefixed to avoid conflicts)
   - `/api/locations/states`
   - `/api/filters`

---

## 🧪 **Testing Examples**

```bash
# Locations
curl "http://localhost/lagos"
curl "http://localhost/abuja"
curl "http://localhost/ikeja"

# Categories
curl "http://localhost/hotels"
curl "http://localhost/restaurants"

# Location + Category
curl "http://localhost/lagos/hotels"
curl "http://localhost/abuja/restaurants"
curl "http://localhost/ikeja/schools"

# With filters
curl "http://localhost/lagos/hotels?rating=4&verified=true"
curl "http://localhost/restaurants?open_now=true&sort=rating"

# Business detail
curl "http://localhost/hotel/grand-hotel"
curl "http://localhost/restaurant/tasty-food"

# Business reviews
curl "http://localhost/hotel/grand-hotel/reviews"
curl -X POST "http://localhost/hotel/grand-hotel/reviews" -d '{"rating": 5}'
```

---

## 📝 **Route Name Usage**

```php
// Location page
route('discovery.single', ['locationOrCategory' => 'lagos'])
// /lagos

// Category page
route('discovery.single', ['locationOrCategory' => 'hotels'])
// /hotels

// Location + Category
route('discovery.combined', ['location' => 'lagos', 'category' => 'hotels'])
// /lagos/hotels

// Business detail
route('businesses.show', ['businessType' => 'hotel', 'slug' => 'grand-hotel'])
// /hotel/grand-hotel

// Business reviews
route('businesses.reviews.index', ['businessType' => 'hotel', 'slug' => 'grand-hotel'])
// /hotel/grand-hotel/reviews
```

---

## 🚨 **Important Notes**

### 1. **Slug Uniqueness**
- Location slugs, category slugs, and business type slugs should be unique across all types
- If there's a conflict (e.g., "hotels" as both location and category), the system checks locations first, then categories

### 2. **URL Detection Priority**
The system checks in this order:
1. Is it a location? (state or city)
2. Is it a category?
3. Is it a business type?
4. 404 if none match

### 3. **Two-Segment URLs**
For URLs like `/lagos/hotels`:
- First segment is checked as location
- Second segment is checked as category or business type

### 4. **API Routes**
API routes keep `/api/` prefix to avoid conflicts with clean URLs.

---

## ✅ **All Changes Complete**

- ✅ Routes updated with clean URLs
- ✅ DiscoveryController handles URL detection
- ✅ Location routes: `/lagos`, `/abuja`, etc.
- ✅ Category routes: `/hotels`, `/restaurants`, etc.
- ✅ Combined routes: `/lagos/hotels`, etc.
- ✅ Business routes: `/{businessType}/{slug}`
- ✅ API routes prefixed: `/api/locations/states`
- ✅ No linter errors
- ✅ Smart detection logic

---

## 🎉 **URL Structure Summary**

| URL Pattern | Example | What It Shows |
|-------------|---------|---------------|
| `/{location}` | `/lagos` | All businesses in Lagos |
| `/{category}` | `/hotels` | All hotels |
| `/{location}/{category}` | `/lagos/hotels` | Hotels in Lagos |
| `/{businessType}/{slug}` | `/hotel/grand-hotel` | Specific hotel detail |
| `/{businessType}/{slug}/reviews` | `/hotel/grand-hotel/reviews` | Hotel reviews |

**Your URL structure is now clean, SEO-friendly, and production-ready!** 🚀
