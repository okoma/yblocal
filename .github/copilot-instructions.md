# AI Coding Instructions for YBLocal

**Project**: Business discovery platform (Yelp-style) with real-time filtering, payments, referrals, and business management.

## Architecture Overview

### Tech Stack
- **Framework**: Laravel 12 + Livewire (real-time components)
- **Admin UI**: Filament 3.3 (3 panels: Admin, Business, Customer)
- **Frontend**: Blade templates + Tailwind CSS 4 + Vite
- **Database**: SQLite (local) / PostgreSQL (production)
- **Payments**: Multi-gateway support (Paystack, etc.)
- **Auth**: Laravel Socialite (Google, Apple) + custom credentials

### Core Domains

**Business Discovery** (`DiscoveryController`, `BusinessFilters` Livewire component)
- URL-synced real-time filtering (state, city, category, ratings, verification status)
- Yelp-style split view: listings (scrollable left) + sticky map (right)
- Clean URLs: `/lagos`, `/hotels`, `/lagos/hotels`
- Tracking: `BusinessClick`, `BusinessImpression`, `BusinessView` models for analytics

**Payment System** (`PaymentService`, `PaymentController`)
- Multi-payable entities: `Subscription` (plans for businesses), `AdCampaign` (advertising), `Wallet` (customer balance)
- Transaction tracking with polymorphic relationships (`Transaction` model: `transactable_type`/`transactable_id`)
- Webhook handling for payment gateway callbacks (`WebhookController`)
- Currency: NGN

**Referral Ecosystem** (`ReferralSignupService`, `ReferralCommissionService`)
- Business referrals → commission credits (not cash withdrawals)
- Customer referrals → wallet transactions (cashable)
- Wallet withdrawal system with pending approval flow
- Separate models: `BusinessReferral`/`BusinessReferralCreditTransaction` vs `CustomerReferral`/`CustomerReferralWallet`

**Business Management** 
- Business owner can claim, verify, manage multiple locations
- Quote requests system (reverse marketplace): businesses bid on customer requests
- Subscription plans with feature limits (`SubscriptionPlan`, `NewBusinessPlanLimits` service)
- Manager invitations for multi-user business access

## Project Conventions

### Services Layer
Located in `app/Services/`, used for **complex multi-step logic** that touches multiple models:
- `PaymentService`: ~950 lines, handles all payment orchestration
- `ReferralSignupService`, `ReferralCommissionService`: referral flows
- `ActivationService`: business activation workflows
- Don't add simple queries—use Controllers or Models directly

### Livewire Components
Located in `app/Livewire/`, use `#[Url]` attributes for **SEO-friendly state**:
```php
#[Url(as: 'business_type', history: true)]
public $businessType = '';
```
This syncs filter state to URL query params, enabling sharable/bookmarkable filtered views. Use `#[Computed]` for derived properties.

### Models & Relationships
- **Polymorphic relations** common: `Transaction` (transactable), `DatabaseNotification` (notifiable)
- **Soft deletes** widely used (`SoftDeletes` trait)
- **Slugs** use Spatie's `HasSlug` trait (e.g., `Business`, `Location`)
- **Many-to-many with pivot data**: Use explicit pivot model when extras needed

### Enums
Located in `app/Enums/`, actively used in migrations and controllers:
- `PageType` (DISCOVERY, DETAIL, CHECKOUT, etc.)
- `InteractionType` (VIEW, CLICK, ADD_REVIEW, etc.)
- `ReferralSource` (AFFILIATE, DIRECT, etc.)

### Data Flows
- **Tracking**: Business interactions auto-logged (`BusinessImpression`, `BusinessClick`) via observers or explicit model updates
- **Async**: Jobs likely used for payments, emails, webhooks (check `app/Jobs/` if it exists)
- **Notifications**: `DatabaseNotification`, `Notification` models + Filament's notification system

## Common Tasks

### Adding a Filter to Business Discovery
1. Add URL property to `BusinessFilters.php` with `#[Url]` attribute
2. Update the query builder in `#[Computed] protected function filteredBusinesses()`
3. Update Blade view to include filter UI (offcanvas drawer)
4. Test: filters should sync to URL automatically

### Processing Payments
1. Call `PaymentService->initializePayment($user, $amount, $gatewayId, $payable)` where `$payable` is Subscription/AdCampaign/Wallet instance
2. Handle webhook in `WebhookController` → verify transaction → update model status
3. Dispatch notification/email after success

### Creating Business Referral Commission
- `ReferralSignupService->recordSignup()` creates `BusinessReferral` + pending credit
- `ReferralCommissionService->approve()` converts to `BusinessReferralCreditTransaction`
- Credits are **non-cashable** (unlike customer wallet)

## Critical Files to Know

- [app/Models/Business.php](app/Models/Business.php) — 1000+ lines, complex model with 40+ relationships
- [app/Services/PaymentService.php](app/Services/PaymentService.php) — multi-entity payment orchestration
- [app/Livewire/BusinessFilters.php](app/Livewire/BusinessFilters.php) — real-time filtering component
- [app/Http/Controllers/DiscoveryController.php](app/Http/Controllers/DiscoveryController.php) — URL parsing for clean routes
- [routes/web.php](routes/web.php) — URL scheme (often uses model binding)

## Development Workflow

```bash
# Dev server (Vite watches frontend)
php artisan serve
npm run dev

# Run tests
php artisan test

# Generate migrations / models
php artisan make:migration ...
php artisan make:model ...

# Tinker (interactive shell)
php artisan tinker
```

## Important Notes

- **Verification required before coding**: Check existing Laravel/Filament patterns in the codebase
- **No random inventions**: Don't add methods/classes without checking existing code
- **Linting**: Run before saving (if configured in project)
- **Database defaults**: SQLite locally—test migrations accordingly
- **Social auth**: Socialite providers configured for Google, Apple (see `config/services.php`)

## Filament Admin Panels

Three separate Filament panels in `app/Providers/Filament/`:
- **Admin**: Super-admin dashboard
- **Business**: Business owner self-service panel
- **Customer**: Customer account & preferences

Resources use standard Filament table/form builders. Relations often use nested forms or separate resource links.

## Capability Assumption

The AI assistant is expected to have full read access to the workspace files.
If file access is unavailable, request it explicitly before analysis.
