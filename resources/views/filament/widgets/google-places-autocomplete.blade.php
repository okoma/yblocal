<div>
    <script>
        // Wait for Google Maps API to load
        function initGooglePlacesAutocomplete() {
            const addressInput = document.querySelector('input[data-google-autocomplete="true"]');
            
            if (!addressInput) {
                console.warn('Address input not found');
                return;
            }
            
            // Initialize Google Places Autocomplete
            const autocomplete = new google.maps.places.Autocomplete(addressInput, {
                types: ['address'],
                componentRestrictions: { country: 'NG' }, // Restrict to Nigeria (change if needed)
                fields: ['formatted_address', 'geometry', 'address_components']
            });
            
            // Listen for place selection
            autocomplete.addListener('place_changed', function() {
                const place = autocomplete.getPlace();
                
                if (!place.geometry) {
                    console.warn('No geometry found for selected place');
                    return;
                }
                
                // Get latitude and longitude
                const lat = place.geometry.location.lat();
                const lng = place.geometry.location.lng();
                
                // Get the Filament Alpine.js component for reactive updates
                const latitudeInput = document.getElementById('latitude-field');
                const longitudeInput = document.getElementById('longitude-field');
                
                if (latitudeInput && longitudeInput) {
                    // Trigger Filament's reactive update
                    const latEvent = new Event('input', { bubbles: true });
                    const lngEvent = new Event('input', { bubbles: true });
                    
                    latitudeInput.value = lat.toFixed(7);
                    longitudeInput.value = lng.toFixed(7);
                    
                    latitudeInput.dispatchEvent(latEvent);
                    longitudeInput.dispatchEvent(lngEvent);
                    
                    // Also trigger change event for Livewire
                    latitudeInput.dispatchEvent(new Event('change', { bubbles: true }));
                    longitudeInput.dispatchEvent(new Event('change', { bubbles: true }));
                    
                    console.log('Coordinates auto-filled:', { lat, lng });
                } else {
                    console.warn('Latitude/Longitude inputs not found');
                }
                
                // Optional: Show success notification
                if (window.$wireui) {
                    $wireui.notify({
                        title: 'Location Selected',
                        description: 'Coordinates have been auto-filled',
                        icon: 'success'
                    });
                }
            });
            
            console.log('Google Places Autocomplete initialized');
        }
        
        // Load Google Maps API
        function loadGoogleMapsAPI() {
            const apiKey = '{{ config("services.google_maps.api_key") }}';
            
            if (!apiKey) {
                console.error('Google Maps API key not configured. Please add GOOGLE_MAPS_API_KEY to your .env file');
                return;
            }
            
            // Check if already loaded
            if (window.google && window.google.maps) {
                initGooglePlacesAutocomplete();
                return;
            }
            
            // Create script tag
            const script = document.createElement('script');
            script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&libraries=places&callback=initGooglePlacesAutocomplete`;
            script.async = true;
            script.defer = true;
            
            document.head.appendChild(script);
        }
        
        // Initialize when page loads
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', loadGoogleMapsAPI);
        } else {
            loadGoogleMapsAPI();
        }
        
        // Re-initialize on Livewire navigation (for SPA-like behavior)
        document.addEventListener('livewire:navigated', () => {
            setTimeout(initGooglePlacesAutocomplete, 100);
        });
        
        // Re-initialize on Filament page load
        document.addEventListener('filament-page-loaded', () => {
            setTimeout(initGooglePlacesAutocomplete, 100);
        });
    </script>
</div>
