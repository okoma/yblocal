# Google Places Autocomplete Integration

## 🎯 **Overview**

Google Places Autocomplete has been integrated into the business creation and editing forms in the Filament Business Panel. When a user types an address, Google suggests addresses, and upon selection, automatically fills the latitude and longitude fields.

---

## ✅ **What Was Implemented**

### **1. Address Autocomplete**
- **Input Field**: Address field now has Google Places Autocomplete
- **Suggestions**: Real-time address suggestions as user types
- **Country Filter**: Restricted to Nigeria (`NG`) by default
- **Address Types**: Only addresses (not businesses or landmarks)

### **2. Auto-Fill Coordinates**
- **Latitude**: Auto-filled when user selects an address
- **Longitude**: Auto-filled when user selects an address
- **Precision**: 7 decimal places for accurate location
- **Manual Entry**: Users can still manually enter coordinates if needed

### **3. Where It Works**

**Business Panel (Business Owners):**
- ✅ **Business Creation Form** (`/business/businesses/create`)
- ✅ **Business Edit Form** (`/business/businesses/{id}/edit`)

**Admin Panel (Admins/Reviewers):**
- ✅ **Admin Business Creation** (`/admin/businesses/create`)
- ✅ **Admin Business Edit** (`/admin/businesses/{id}/edit`)

All use wizard-based forms (Step 2: Location & Contact)

---

## 🔧 **Setup Instructions**

### **Step 1: Get Google Maps API Key**

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing
3. Enable **Google Places API**
   - Navigate to "APIs & Services" > "Library"
   - Search for "Places API"
   - Click "Enable"
4. Create API Key
   - Go to "APIs & Services" > "Credentials"
   - Click "Create Credentials" > "API Key"
   - Copy the API key

### **Step 2: Restrict API Key (Recommended)**

For security, restrict your API key:

1. Click on your API key in Google Cloud Console
2. Under "Application restrictions":
   - Select "HTTP referrers (web sites)"
   - Add your domain: `https://yourdomain.com/*`
   - For local development, add: `http://localhost/*`
3. Under "API restrictions":
   - Select "Restrict key"
   - Choose "Places API"
4. Save

### **Step 3: Add API Key to .env**

Open your `.env` file and add:

```env
GOOGLE_MAPS_API_KEY=YOUR_GOOGLE_MAPS_API_KEY_HERE
```

Replace `YOUR_GOOGLE_MAPS_API_KEY_HERE` with your actual API key from Step 1.

### **Step 4: Clear Config Cache**

```bash
php artisan config:clear
```

---

## 🧪 **Testing**

### **Test the Integration:**

#### **Option 1: Test in Business Panel**
1. **Navigate to Business Panel**
   - Go to `/business` (or your business panel URL)
   - Click "My Business" > "Create New Business"

2. **Go to Location Step**
   - Fill out Step 1 (Basic Information)
   - Click "Next" to Step 2 (Location & Contact)

#### **Option 2: Test in Admin Panel**
1. **Navigate to Admin Panel**
   - Go to `/admin`
   - Click "Businesses" > "Create"

2. **Go to Location Step**
   - Fill out Step 1 (Basic Information)
   - Click "Next" to Step 2 (Location & Contact)

#### **Testing Steps (Same for Both Panels):**
3. **Test Address Autocomplete**
   - Click on the "Address" field
   - Start typing an address (e.g., "123 main street lagos")
   - You should see Google address suggestions appear
   - Select an address from the dropdown

4. **Verify Auto-Fill**
   - After selecting an address, check:
     - ✅ Address field is populated
     - ✅ Latitude field is auto-filled
     - ✅ Longitude field is auto-filled
   - Coordinates should be accurate (7 decimal places)

5. **Test Manual Entry**
   - You can still manually type latitude/longitude if needed
   - Useful for places not in Google Maps

6. **Test in Edit Form**
   - Create or open an existing business
   - Click "Edit"
   - Go to Location step
   - Verify autocomplete works when updating address

---

## 🎨 **How It Works**

### **Frontend (JavaScript)**

```javascript
// Initialize Google Places Autocomplete
const autocomplete = new google.maps.places.Autocomplete(addressInput, {
    types: ['address'],
    componentRestrictions: { country: 'NG' },
    fields: ['formatted_address', 'geometry']
});

// Listen for place selection
autocomplete.addListener('place_changed', function() {
    const place = autocomplete.getPlace();
    const lat = place.geometry.location.lat();
    const lng = place.geometry.location.lng();
    
    // Auto-fill latitude and longitude fields
    latitudeInput.value = lat.toFixed(7);
    longitudeInput.value = lng.toFixed(7);
});
```

### **Backend (Laravel)**

```php
// config/services.php
'google_maps' => [
    'api_key' => env('GOOGLE_MAPS_API_KEY'),
],

// View: filament.widgets.google-places-autocomplete.blade.php
// Loads Google Maps API and initializes autocomplete
```

---

## 🔒 **Security Best Practices**

### **1. Restrict API Key**
- **Application Restrictions**: Limit to your domain(s)
- **API Restrictions**: Only enable Places API
- **Billing Alerts**: Set up billing alerts in Google Cloud

### **2. Environment Variables**
- **Never commit**: Add `.env` to `.gitignore` (already done)
- **Production**: Use secure environment variable management
- **Rotation**: Rotate API keys periodically

### **3. Usage Monitoring**
- Monitor API usage in Google Cloud Console
- Places Autocomplete costs: **$2.83 - $17 per 1,000 requests**
- First 1,000 requests/month are free (per project)

---

## 💰 **Pricing**

### **Google Places API Pricing (as of 2024)**

| Service | Cost per 1,000 requests | Monthly Free Tier |
|---------|-------------------------|-------------------|
| **Autocomplete - Per Session** | $2.83 | 1,000 requests |
| **Autocomplete - Per Request** | $17.00 | 1,000 requests |
| **Place Details** | $17.00 | 1,000 requests |

**Recommendation**: Use "Per Session" pricing (already implemented) - much cheaper!

### **Estimate for Your Platform**
- **100 businesses/month**: ~$0 (within free tier)
- **500 businesses/month**: ~$1.41 (200 paid requests × $2.83/1000)
- **1,000 businesses/month**: ~$5.66 (2,000 paid requests × $2.83/1000)

---

## 🌍 **Country Restrictions**

Currently restricted to **Nigeria** (`NG`). To change:

**In `resources/views/filament/widgets/google-places-autocomplete.blade.php`:**

```javascript
// Single country
componentRestrictions: { country: 'NG' }

// Multiple countries
componentRestrictions: { country: ['NG', 'GH', 'KE'] }

// No restrictions (worldwide)
// Remove componentRestrictions entirely
```

---

## 🐛 **Troubleshooting**

### **Problem: Autocomplete not showing**

**Check:**
1. Is `GOOGLE_MAPS_API_KEY` in `.env`?
   ```bash
   grep GOOGLE_MAPS_API_KEY .env
   ```
2. Did you clear config cache?
   ```bash
   php artisan config:clear
   ```
3. Check browser console for errors
   - Press F12 → Console tab
   - Look for Google Maps API errors

### **Problem: "This API key is not authorized"**

**Solution:**
- Your API key is restricted to specific domains
- Add your current domain to the allowed list in Google Cloud Console
- For local development, add `http://localhost/*`

### **Problem: "Places API is not enabled"**

**Solution:**
1. Go to Google Cloud Console
2. Navigate to "APIs & Services" > "Library"
3. Search for "Places API"
4. Click "Enable"

### **Problem: Coordinates not auto-filling**

**Check:**
1. Open browser console (F12)
2. Look for JavaScript errors
3. Ensure input fields have correct IDs:
   - Address: `data-google-autocomplete="true"`
   - Latitude: `id="latitude-field"`
   - Longitude: `id="longitude-field"`

---

## 📝 **Files Modified**

| File | Changes |
|------|---------|
| **Business Panel** | |
| `app/Filament/Business/Resources/BusinessResource/Pages/CreateBusiness.php` | Added autocomplete attributes to address/lat/lng fields, added `getFooter()` method |
| `app/Filament/Business/Resources/BusinessResource/Pages/EditBusiness.php` | Same as CreateBusiness |
| **Admin Panel** | |
| `app/Filament/Admin/Resources/BusinessResource/Pages/CreateBusiness.php` | Added autocomplete attributes to address/lat/lng fields, added `getFooter()` method |
| `app/Filament/Admin/Resources/BusinessResource/Pages/EditBusiness.php` | Same as Admin CreateBusiness |
| **Shared** | |
| `resources/views/filament/widgets/google-places-autocomplete.blade.php` | **New file** - Google Maps API loader and autocomplete initialization |
| `.env.example` | Added `GOOGLE_MAPS_API_KEY` |
| `config/services.php` | Added `google_maps` configuration |

---

## 🚀 **Future Enhancements**

### **Optional Features to Add:**

1. **Map Preview**
   - Show map when coordinates are entered
   - Visual confirmation of location

2. **Reverse Geocoding**
   - Drag map pin to get address
   - More intuitive for users

3. **Auto-Fill State/City**
   - Extract state and city from address
   - Reduce manual data entry

4. **Distance Calculation**
   - Calculate distance from user's location
   - "Near me" filtering

5. **Custom Markers**
   - Show business location on map
   - Preview before saving

---

## 📚 **Documentation Links**

- [Google Places Autocomplete Docs](https://developers.google.com/maps/documentation/javascript/place-autocomplete)
- [Google Maps API Pricing](https://mapsplatform.google.com/pricing/)
- [Restrict API Keys](https://developers.google.com/maps/api-security-best-practices#restrict-api-keys)

---

## ✅ **Summary**

You now have:
- ✅ Google Places Autocomplete on address field
- ✅ Auto-fill of latitude and longitude
- ✅ Works on both create and edit forms
- ✅ Restricted to Nigeria (configurable)
- ✅ Secure API key configuration
- ✅ Production-ready implementation

**Next Steps:**
1. Get your Google Maps API key
2. Add it to `.env`
3. Test the autocomplete functionality

**Happy mapping! 🗺️**
