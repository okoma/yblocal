# Yelp-Style Split Layout: Listings + Map

## 🎨 Layout Overview

The business discovery page now uses a **Yelp-inspired split layout**:

```
┌─────────────────────────────────────────────────────────┐
│  [Filters] [Search Bar..................] [Sort ▼]      │ ← Sticky Header
├──────────────────────┬──────────────────────────────────┤
│                      │                                  │
│   BUSINESS LISTINGS  │         MAP VIEW                 │
│                      │                                  │
│  ┌────────────────┐  │  ┌───────────────────────────┐  │
│  │ Business Card  │  │  │                           │  │
│  └────────────────┘  │  │                           │  │
│                      │  │      🗺️ Interactive        │  │
│  ┌────────────────┐  │  │         Map               │  │
│  │ Business Card  │  │  │                           │  │
│  └────────────────┘  │  │    (Markers for each      │  │
│                      │  │     business)             │  │
│  ┌────────────────┐  │  │                           │  │
│  │ Business Card  │  │  │                           │  │
│  └────────────────┘  │  └───────────────────────────┘  │
│                      │                                  │
│  [Pagination]        │         (Sticky)                 │
│                      │                                  │
│  (Scrollable)        │        (Fixed Position)          │
└──────────────────────┴──────────────────────────────────┘
       50% width                  50% width
```

---

## 📱 **Responsive Behavior**

### **Desktop (≥1024px):**
- **Left Half**: Business listings (scrollable)
- **Right Half**: Map (sticky, stays in view)
- Split is 50/50
- Both sides visible simultaneously

### **Tablet & Mobile (<1024px):**
- **Full Width**: Business listings only
- **Map**: Hidden (can be added as toggle if needed)
- Optimized for touch scrolling

---

## 🎯 **Key Features**

### **1. Filter Offcanvas (Universal)**
- **Desktop**: Slides in from the right (full height)
- **Mobile**: Slides in from the right (full screen)
- **Trigger**: Blue "Filters" button (top left)
- **Badge**: Shows active filter count

### **2. Business Listings (Left Panel)**
- Vertical list of business cards
- Infinite scroll or pagination
- Each card is clickable
- Hover effects for better UX
- Loading states during filtering

### **3. Map View (Right Panel - Desktop Only)**
- **Sticky Position**: Stays visible while scrolling listings
- **Markers**: One for each business in results
- **Interactive**: Click marker to highlight business
- **Zoom**: Adjusts based on business locations
- **Real-time Updates**: Map markers update when filters change

---

## 🔧 **Implementation Details**

### **HTML Structure**

```blade
<div class="flex flex-col lg:flex-row h-[calc(100vh-180px)]">
    <!-- Left: Listings -->
    <div class="w-full lg:w-1/2 overflow-y-auto">
        <!-- Business cards here -->
    </div>
    
    <!-- Right: Map (Desktop Only) -->
    <div class="hidden lg:block lg:w-1/2 sticky top-[180px]">
        <div id="business-map" class="w-full h-full">
            <!-- Map renders here -->
        </div>
    </div>
</div>
```

### **CSS Classes Breakdown**

| Class | Purpose |
|-------|---------|
| `flex flex-col lg:flex-row` | Stack on mobile, side-by-side on desktop |
| `h-[calc(100vh-180px)]` | Full height minus header |
| `w-full lg:w-1/2` | Full width mobile, 50% desktop |
| `overflow-y-auto` | Scrollable listings |
| `sticky top-[180px]` | Map stays in view while scrolling |
| `hidden lg:block` | Hide map on mobile |

---

## 🗺️ **Map Integration**

### **Placeholder (Current)**
Currently showing a placeholder. To integrate a real map:

### **Option 1: Google Maps**

```javascript
// In @push('scripts')
<script>
    let map;
    let markers = [];
    
    function initMap() {
        map = new google.maps.Map(document.getElementById('business-map'), {
            center: { lat: 6.5244, lng: 3.3792 }, // Lagos, Nigeria
            zoom: 12
        });
        
        // Add markers for each business
        @foreach($businesses as $business)
            @if($business->latitude && $business->longitude)
                const marker = new google.maps.Marker({
                    position: { 
                        lat: {{ $business->latitude }}, 
                        lng: {{ $business->longitude }} 
                    },
                    map: map,
                    title: "{{ $business->business_name }}"
                });
                
                marker.addListener('click', () => {
                    window.location.href = "{{ $business->getUrl() }}";
                });
                
                markers.push(marker);
            @endif
        @endforeach
    }
</script>
<script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&callback=initMap" async defer></script>
```

### **Option 2: Leaflet (Open Source)**

```javascript
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
    const map = L.map('business-map').setView([6.5244, 3.3792], 12);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    // Add markers
    @foreach($businesses as $business)
        @if($business->latitude && $business->longitude)
            L.marker([{{ $business->latitude }}, {{ $business->longitude }}])
                .addTo(map)
                .bindPopup(`
                    <strong>{{ $business->business_name }}</strong><br>
                    <a href="{{ $business->getUrl() }}">View Details</a>
                `);
        @endif
    @endforeach
</script>
```

### **Option 3: Mapbox**

```javascript
<script src='https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.js'></script>
<link href='https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.css' rel='stylesheet' />

<script>
    mapboxgl.accessToken = 'YOUR_MAPBOX_TOKEN';
    
    const map = new mapboxgl.Map({
        container: 'business-map',
        style: 'mapbox://styles/mapbox/streets-v12',
        center: [3.3792, 6.5244], // Lagos
        zoom: 12
    });
    
    // Add markers
    @foreach($businesses as $business)
        @if($business->latitude && $business->longitude)
            new mapboxgl.Marker()
                .setLngLat([{{ $business->longitude }}, {{ $business->latitude }}])
                .setPopup(
                    new mapboxgl.Popup().setHTML(`
                        <h3>{{ $business->business_name }}</h3>
                        <a href="{{ $business->getUrl() }}">View</a>
                    `)
                )
                .addTo(map);
        @endif
    @endforeach
</script>
```

---

## 🎯 **Map Features to Add**

### **Phase 1: Basic**
- [x] Placeholder map area
- [ ] Real map integration (Google/Leaflet/Mapbox)
- [ ] Markers for each business
- [ ] Click marker to view business

### **Phase 2: Enhanced**
- [ ] Cluster markers when zoomed out
- [ ] Custom marker icons (verified, premium)
- [ ] Hover business card → highlight marker
- [ ] Click marker → scroll to business card
- [ ] Current location button

### **Phase 3: Advanced**
- [ ] Draw search area on map
- [ ] Distance radius filter
- [ ] Directions from current location
- [ ] Street view integration
- [ ] Heat map for popular areas

---

## 🔄 **Livewire Integration**

### **Update Map When Filters Change**

```blade
<div 
    x-data="{ 
        businesses: @entangle('businesses') 
    }"
    x-init="
        $watch('businesses', value => {
            // Update map markers when businesses change
            updateMapMarkers(value);
        })
    "
>
    <!-- Map div -->
</div>

<script>
    function updateMapMarkers(businesses) {
        // Clear existing markers
        markers.forEach(m => m.remove());
        markers = [];
        
        // Add new markers
        businesses.data.forEach(business => {
            if (business.latitude && business.longitude) {
                const marker = new google.maps.Marker({
                    position: { 
                        lat: business.latitude, 
                        lng: business.longitude 
                    },
                    map: map,
                    title: business.business_name
                });
                markers.push(marker);
            }
        });
        
        // Fit bounds to show all markers
        if (markers.length > 0) {
            const bounds = new google.maps.LatLngBounds();
            markers.forEach(m => bounds.extend(m.getPosition()));
            map.fitBounds(bounds);
        }
    }
</script>
```

---

## 📐 **Layout Customization**

### **Change Split Ratio**

```blade
<!-- 60/40 split instead of 50/50 -->
<div class="w-full lg:w-3/5"><!-- Listings (60%) --></div>
<div class="w-full lg:w-2/5"><!-- Map (40%) --></div>

<!-- 70/30 split -->
<div class="w-full lg:w-[70%]"><!-- Listings --></div>
<div class="w-full lg:w-[30%]"><!-- Map --></div>
```

### **Make Map Collapsible**

```blade
<div x-data="{ showMap: true }">
    <!-- Toggle Button -->
    <button @click="showMap = !showMap" class="lg:hidden">
        <span x-show="!showMap">Show Map</span>
        <span x-show="showMap">Hide Map</span>
    </button>
    
    <!-- Map (toggleable on mobile) -->
    <div x-show="showMap" class="lg:block">
        <!-- Map content -->
    </div>
</div>
```

### **Adjust Heights**

```blade
<!-- Taller header -->
h-[calc(100vh-200px)]  <!-- Adjust 200px to your header height -->

<!-- Shorter listings area -->
h-[calc(100vh-300px)]  <!-- More space for other content -->
```

---

## 🎨 **Similar Platforms**

Your implementation is inspired by:

1. **Yelp**: Split view with listings and map
2. **Google Maps**: Business listings with interactive map
3. **Airbnb**: Property listings with map
4. **TripAdvisor**: Reviews/listings + map view
5. **Zillow**: Real estate listings + map

---

## 🚀 **Performance Tips**

### **Lazy Load Map**
```javascript
// Only load map when visible
const observer = new IntersectionObserver((entries) => {
    if (entries[0].isIntersecting) {
        initMap();
        observer.disconnect();
    }
});

observer.observe(document.getElementById('business-map'));
```

### **Marker Clustering**
For 100+ businesses, use marker clustering:

```javascript
// With Google Maps
import MarkerClusterer from '@googlemaps/markerclustererplus';

const markerCluster = new MarkerClusterer(map, markers, {
    imagePath: 'https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/m',
});
```

### **Pagination for Performance**
- Show 20-50 businesses per page
- Load more on scroll (infinite scroll)
- Update map markers progressively

---

## 📱 **Mobile Enhancements**

### **Add Map Toggle Button**

```blade
<!-- Show on mobile only -->
<button 
    @click="showMap = !showMap"
    class="lg:hidden fixed bottom-4 right-4 bg-blue-600 text-white px-4 py-2 rounded-full shadow-lg z-50"
>
    <svg class="w-5 h-5"><!-- Map icon --></svg>
    Map View
</button>

<!-- Full-screen map modal on mobile -->
<div 
    x-show="showMap" 
    x-cloak
    class="lg:hidden fixed inset-0 z-50 bg-white"
>
    <div id="mobile-map" class="w-full h-full"></div>
    <button @click="showMap = false" class="absolute top-4 right-4">
        Close
    </button>
</div>
```

---

## ✨ **You Now Have:**

- ✅ **Yelp-style split layout**
- ✅ **Listings on left (scrollable)**
- ✅ **Map on right (sticky, desktop only)**
- ✅ **Offcanvas filters (all devices)**
- ✅ **Livewire real-time updates**
- ✅ **SEO-friendly URLs**
- ✅ **Responsive design**
- ✅ **Ready for map integration**

**Next Step:** Choose and integrate a map provider (Google Maps, Leaflet, or Mapbox) to display business locations! 🗺️
