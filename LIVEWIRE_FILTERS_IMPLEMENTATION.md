# Livewire Filters Implementation with SEO Support

## 🎯 Overview

The business discovery and filtering system has been implemented using **Laravel Livewire** with full SEO support. This provides:

- ✅ **Real-time filtering** without page reloads
- ✅ **URL synchronization** for shareability and SEO
- ✅ **Browser history** support (back/forward buttons work)
- ✅ **Bookmarkable URLs** with filters preserved
- ✅ **Loading states** for better UX
- ✅ **Instant search** with debouncing
- ✅ **Clean URLs** (e.g., `/lagos`, `/hotels`, `/lagos/hotels`)

---

## 📁 Files Structure

```
app/
├── Livewire/
│   └── BusinessFilters.php          # Main Livewire component
├── Http/Controllers/
│   └── DiscoveryController.php      # Routes handler, passes context to Livewire

resources/views/
├── livewire/
│   └── business-filters.blade.php   # Livewire component view
├── businesses/
│   └── discovery.blade.php          # Main page wrapper
└── components/
    └── business-card.blade.php      # Reusable business card component
```

---

## 🔧 How It Works

### 1. **URL Synchronization (SEO)**

All filter properties are bound to the URL using Livewire's `#[Url]` attribute:

```php
#[Url(as: 'business_type', history: true)]
public $businessType = '';

#[Url(as: 'category', history: true)]
public $category = '';

#[Url(as: 'state', history: true)]
public $state = '';
```

**Result:**
- Changing filters updates the URL: `?business_type=hotel&state=lagos&rating=4`
- URL changes are added to browser history
- Users can share filtered results
- Search engines can index filtered pages

### 2. **Real-time Filtering**

Filters use `wire:model.live` for instant updates:

```blade
<select wire:model.live="state">
    <option value="">All States</option>
    @foreach($states as $state)
        <option value="{{ $state->slug }}">{{ $state->name }}</option>
    @endforeach
</select>
```

**Flow:**
1. User selects filter
2. Livewire updates property
3. URL updates automatically
4. `businesses()` computed property re-runs
5. Results update instantly (no page reload)

### 3. **Computed Properties**

Filters and results are cached using `#[Computed]`:

```php
#[Computed]
public function businesses()
{
    $query = Business::with(['businessType', 'categories', 'location.parent'])
        ->where('is_published', true);
    
    // Apply filters...
    
    return $query->paginate(12);
}
```

**Benefits:**
- Results are cached until properties change
- Reduces database queries
- Improves performance

### 4. **Context-Aware Filtering**

The component accepts context from routes:

```php
public function mount($location = null, $category = null, $businessType = null, $search = '')
{
    $this->contextLocation = $location;
    $this->contextCategory = $category;
    $this->contextBusinessType = $businessType;
}
```

**Examples:**
- `/lagos` → Context: Location (Lagos)
- `/hotels` → Context: Category (Hotels)
- `/lagos/hotels` → Context: Location + Category
- Search query preserved from route

---

## 🎨 UI Components

### 1. **Filter Canvas (Offcanvas Drawer)**

```blade
<div 
    x-data="{ show: @entangle('showFilters') }"
    x-show="show"
    class="fixed inset-0 z-50"
>
    <!-- Backdrop -->
    <div @click="show = false" class="fixed inset-0 bg-black bg-opacity-50"></div>
    
    <!-- Drawer -->
    <div class="fixed right-0 top-0 h-full w-full sm:w-96 bg-white">
        <!-- Filter options... -->
    </div>
</div>
```

**Features:**
- Slides in from the right
- Backdrop click to close
- Smooth transitions (Alpine.js)
- Responsive width
- Scroll-locked body when open

### 2. **Active Filters Pills**

```blade
@if($activeFiltersCount > 0)
    <div class="flex flex-wrap gap-2">
        @if($businessType)
            <span class="inline-flex items-center gap-1 px-3 py-1 bg-blue-100 rounded-full text-sm">
                Type: {{ $businessType }}
                <button wire:click="clearFilter('businessType')">×</button>
            </span>
        @endif
    </div>
@endif
```

**Features:**
- Visual indication of active filters
- One-click removal
- Color-coded by filter type
- Badge count on filter button

### 3. **Loading States**

```blade
<div wire:loading.delay class="fixed inset-0 bg-black bg-opacity-20 z-40">
    <div class="bg-white rounded-lg p-6 shadow-xl">
        <svg class="animate-spin h-8 w-8 text-blue-600 mx-auto" ...></svg>
        <p class="mt-2 text-gray-600">Loading...</p>
    </div>
</div>
```

**Features:**
- Appears after 200ms delay (prevents flash for fast queries)
- Spinner animation
- Overlay prevents interaction during loading

---

## 🚀 Available Filters

| Filter | Type | URL Param | Description |
|--------|------|-----------|-------------|
| **Business Type** | Radio | `business_type` | Filter by business type (hotel, restaurant, etc.) |
| **Category** | Radio | `category` | Filter by category |
| **State** | Select | `state` | Filter by state (dynamically loads cities) |
| **City** | Select | `city` | Filter by city (requires state selection) |
| **Rating** | Radio | `rating` | Minimum rating (1-5 stars) |
| **Verified** | Checkbox | `verified` | Show only verified businesses |
| **Premium** | Checkbox | `premium` | Show only premium listings |
| **Open Now** | Checkbox | `open_now` | Show only currently open businesses |
| **Search** | Text | `q` | Search by name, description, tags |
| **Sort** | Select | `sort` | Sort order (relevance, rating, newest, name) |

---

## 🔍 SEO Features

### 1. **Dynamic Page Titles**

```php
private function prepareSeoData(Request $request, array $context): array
{
    if ($request->filled('q')) {
        return [
            'pageTitle' => "Search: {$query} - Find Businesses",
            'metaDescription' => "Search results for {$query}...",
        ];
    }
    // ... more conditions
}
```

### 2. **Meta Descriptions**

Each page type has a unique, SEO-optimized meta description:

- **Search:** "Search results for {query}. Find verified local businesses..."
- **Location:** "Discover trusted businesses in {location}. Browse verified listings..."
- **Category:** "Explore {category} businesses. Find verified providers..."

### 3. **Clean URLs**

```
✅ /lagos                    → Location page
✅ /hotels                   → Category page
✅ /lagos/hotels             → Location + Category
✅ /hotel/grand-hotel        → Business detail
✅ /lagos?rating=4&verified=1 → Filtered results (shareable)
```

### 4. **Schema.org Structured Data**

Ready for implementation in `layouts/app.blade.php`:

```html
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "LocalBusiness",
    "name": "{{ $business->name }}",
    "image": "{{ $business->logo_url }}",
    "address": { ... }
}
</script>
```

---

## 📊 Performance Optimizations

### 1. **Computed Properties Caching**

```php
#[Computed]
public function businesses()
{
    // This query only runs when filter properties change
}
```

### 2. **Eager Loading**

```php
$query->with(['businessType', 'categories', 'location.parent'])
```

Prevents N+1 queries when displaying business cards.

### 3. **Pagination**

```php
return $query->paginate(12);
```

Loads results in chunks of 12.

### 4. **Debounced Search**

```blade
<input wire:model.live.debounce.500ms="search" ...>
```

Waits 500ms after user stops typing before searching.

---

## 🎯 Usage Examples

### **Programmatic Filter Updates**

```javascript
// Update filter from JavaScript
Livewire.dispatch('updateFilter', { filter: 'state', value: 'lagos' });
```

### **Clear Specific Filter**

```php
// In Livewire component
public function clearFilter($filter)
{
    $this->$filter = '';
    if ($filter === 'state') {
        $this->city = ''; // Clear dependent filters
    }
    $this->resetPage();
}
```

### **Clear All Filters**

```php
public function clearFilters()
{
    $this->reset([
        'businessType', 'category', 'state', 'city',
        'rating', 'verified', 'premium', 'openNow', 'sort'
    ]);
    $this->resetPage();
}
```

---

## 🔧 Configuration

### **Pagination Size**

In `BusinessFilters.php`:

```php
return $query->paginate(12); // Change to 20, 30, etc.
```

### **Search Debounce Delay**

In `business-filters.blade.php`:

```blade
<input wire:model.live.debounce.500ms="search"> <!-- Change to 300ms, 1000ms, etc. -->
```

### **Default Sort Order**

In `BusinessFilters.php`:

```php
#[Url(as: 'sort', history: true)]
public $sort = 'relevance'; // Change to 'rating', 'newest', etc.
```

---

## 🧪 Testing

### **URL Sync Test**

1. Visit `/lagos`
2. Open filters and select "Hotels"
3. Check URL: should be `/lagos?category=hotels`
4. Copy URL and paste in new tab
5. Verify filters are pre-selected

### **Browser History Test**

1. Apply multiple filters
2. Click browser back button
3. Verify filters revert to previous state
4. Click forward button
5. Verify filters reapply

### **Search Test**

1. Enter search term
2. Verify results update in real-time (after debounce)
3. Check URL includes `?q=search-term`
4. Clear search
5. Verify URL param removed

---

## 🚨 Common Issues

### **Issue: Filters not updating**

**Cause:** Missing `wire:model.live`

**Fix:**
```blade
<!-- Wrong -->
<select wire:model="state">

<!-- Correct -->
<select wire:model.live="state">
```

### **Issue: URL not updating**

**Cause:** Missing `#[Url]` attribute

**Fix:**
```php
// Wrong
public $state = '';

// Correct
#[Url(as: 'state', history: true)]
public $state = '';
```

### **Issue: Filters reset on page refresh**

**Cause:** Properties not bound to URL

**Solution:** Ensure all filter properties have `#[Url]` attribute.

---

## 📚 Next Steps

1. **Add Filter Analytics**: Track which filters users apply most
2. **Saved Searches**: Allow users to save filter combinations
3. **Recent Searches**: Show user's recent filter history
4. **Filter Presets**: Create quick filter presets ("Top Rated", "Open Now", etc.)
5. **Map Integration**: Show filtered results on interactive map

---

## 🎓 Learn More

- [Livewire Docs](https://livewire.laravel.com/docs)
- [URL Query Parameters](https://livewire.laravel.com/docs/url)
- [Computed Properties](https://livewire.laravel.com/docs/computed-properties)
- [Loading States](https://livewire.laravel.com/docs/loading)
- [Alpine.js for Transitions](https://alpinejs.dev/directives/transition)
