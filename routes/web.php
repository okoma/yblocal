<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Webhook\EmailWebhookController;
use App\Http\Controllers\Notification\UnsubscribeController;
use App\Http\Controllers\ManagerInvitationController;
use App\Http\Controllers\BusinessController;
use App\Livewire\CreateGuestBusiness;
use App\Http\Controllers\DiscoveryController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\BusinessTypeController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\FilterController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\CustomerSocialAuthController;


//welcome
Route::get('/', function () {
    return view('welcome');
});
// Redirect default login to customer login
Route::get('/login', function () {
    return redirect()->route('filament.customer.auth.login');
})->name('login');

// Guest Business Creation Route
Route::get('/list-your-business', CreateGuestBusiness::class)
    ->name('guest.business.create');

// Mailer webhooks (bounces/unsubscribes)
Route::post('/webhooks/mailer/bounce', [EmailWebhookController::class, 'bounce']);
Route::get('/unsubscribe', [UnsubscribeController::class, 'handle'])->name('unsubscribe');

// ============================================
// CUSTOMER SOCIAL AUTH (MUST BE ABOVE CLEAN URL ROUTES)
// ============================================
Route::prefix('customer/auth')->name('customer.auth.')->group(function () {
    Route::get('/{provider}/redirect', [CustomerSocialAuthController::class, 'redirect'])->name('redirect');
    Route::get('/{provider}/callback', [CustomerSocialAuthController::class, 'callback'])->name('callback');
});

// ============================================
// DISCOVERY & SEARCH ROUTES
// Unified discovery for all listing pages  
// Apply rate limiting to prevent scraping
// ============================================
Route::middleware('throttle:discovery')->prefix('discover')->name('discover.')->group(function () {
    Route::get('/', [DiscoveryController::class, 'index'])->name('index');
});

// Public Business Routes (with tracking and rate limiting)
Route::middleware('throttle:discovery')->prefix('businesses')->name('businesses.')->group(function () {
    // Business listing (redirects to discover)
    Route::get('/', [DiscoveryController::class, 'index'])->name('index');
    
    // Business search (redirects to discover)
    Route::get('/search', [DiscoveryController::class, 'index'])->name('search');
});

// Business Type Based Routes (e.g., /hotel/grand-hotel, /restaurant/my-restaurant)
Route::name('businesses.')->group(function () {
    // Single business detail page
    Route::get('/{businessType}/{slug}', [BusinessController::class, 'show'])->name('show');
    
    // Reviews (Public)
    Route::get('/{businessType}/{slug}/reviews', [ReviewController::class, 'index'])->name('reviews.index');
    Route::post('/{businessType}/{slug}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    
    // Leads/Inquiries (Public)
    Route::post('/{businessType}/{slug}/leads', [LeadController::class, 'store'])->name('leads.store');
    
    // Photos/Gallery (Public)
    Route::get('/{businessType}/{slug}/photos', [PhotoController::class, 'index'])->name('photos.index');
    Route::post('/{businessType}/{slug}/photos', [PhotoController::class, 'store'])->name('photos.store'); // Optional: for user submissions
    Route::delete('/{businessType}/{slug}/photos/{photoPath}', [PhotoController::class, 'destroy'])->name('photos.destroy'); // Optional

    // Business Claim & Report (auth required)
    Route::middleware('auth')->group(function () {
        Route::post('/{businessType}/{slug}/claim', [\App\Http\Controllers\BusinessClaimController::class, 'store'])
            ->name('claim.store');

        Route::post('/{businessType}/{slug}/report', [\App\Http\Controllers\BusinessReportController::class, 'store'])
            ->name('report.store');
    });

    // Saved Businesses (requires auth)
    Route::middleware('auth')->group(function () {
        Route::post('/{businessType}/{slug}/save', [\App\Http\Controllers\SavedBusinessController::class, 'store'])
            ->name('save.store');

        Route::delete('/{businessType}/{slug}/save', [\App\Http\Controllers\SavedBusinessController::class, 'destroy'])
            ->name('save.destroy');
    });
});

// Quote requests (customers)
Route::middleware('auth')->post('/quotes', [\App\Http\Controllers\QuoteRequestController::class, 'store'])->name('quotes.store');

// Filter Routes (AJAX endpoints for filter metadata) with rate limiting
Route::middleware('throttle:api')->prefix('api/filters')->name('filters.')->group(function () {
    Route::get('/', [FilterController::class, 'index'])->name('index');
    Route::get('/states/{stateSlug}/cities', [FilterController::class, 'getCitiesByState'])->name('cities.by-state');
});

// API Routes for Locations (keep prefix to avoid conflicts) with rate limiting
Route::middleware('throttle:api')->prefix('api')->name('api.')->group(function () {
    Route::get('locations/states', [LocationController::class, 'getStates'])->name('locations.states');
    Route::get('locations/states/{stateSlug}/cities', [LocationController::class, 'getCitiesByState'])->name('locations.cities');
});

// Review Voting (Optional - for helpful/not helpful)
Route::prefix('reviews')->name('reviews.')->group(function () {
    Route::post('/{reviewId}/vote', [ReviewController::class, 'vote'])->name('vote');
});

// Map Routes (For map-based business discovery)
Route::prefix('map')->name('map.')->group(function () {
    Route::get('/businesses', [MapController::class, 'index'])->name('businesses.index');
    Route::get('/businesses/{slug}', [MapController::class, 'show'])->name('businesses.show');
    Route::get('/nearby', [MapController::class, 'nearby'])->name('nearby');
    Route::get('/cluster', [MapController::class, 'cluster'])->name('cluster');
});


// Manager Invitation Routes (Public)
Route::prefix('manager/invitation')->name('manager.invitation.')->group(function () {
    Route::get('/{token}', [ManagerInvitationController::class, 'show'])
        ->name('accept');
    
    Route::post('/{token}/accept', [ManagerInvitationController::class, 'accept'])
        ->name('accept.submit');
    
    Route::post('/{token}/decline', [ManagerInvitationController::class, 'decline'])
        ->name('decline');
});

// Payment Webhooks (Server-to-Server notifications)
Route::prefix('webhooks')->name('webhooks.')->group(function () {
    Route::post('/paystack', [\App\Http\Controllers\PaymentController::class, 'paystackWebhook'])->name('paystack');
    Route::post('/flutterwave', [\App\Http\Controllers\PaymentController::class, 'flutterwaveWebhook'])->name('flutterwave');
});

// Payment Callbacks (User redirects after payment)
Route::prefix('payment')->name('payment.')->group(function () {
    Route::get('/paystack/callback', [\App\Http\Controllers\PaymentController::class, 'paystackCallback'])->name('paystack.callback');
    Route::get('/flutterwave/callback', [\App\Http\Controllers\PaymentController::class, 'flutterwaveCallback'])->name('flutterwave.callback');
});

// Transaction Receipt (Business Panel)
Route::middleware(['auth'])->group(function () {
    Route::get('/business/transaction/{transaction}/receipt', [\App\Http\Controllers\PaymentController::class, 'downloadReceipt'])
        ->name('business.transaction.receipt');
});

// ============================================
// CLEAN URL ROUTES (MUST BE LAST!)
// These handle: /lagos, /hotels, /lagos/hotels
// Order: Most specific routes first
// ============================================
Route::get('/{location}/{category}', [DiscoveryController::class, 'index'])->name('discovery.combined');
Route::get('/{locationOrCategory}', [DiscoveryController::class, 'index'])->name('discovery.single');