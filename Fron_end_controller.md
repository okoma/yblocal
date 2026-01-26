# Frontend Controllers – Business Directory (MVP+)

## Scope

Controllers that power the **public-facing business directory experience**.
Includes discovery, listings, reviews, media, and contact actions.

Excluded:
- Admin functionality
- Business owner dashboards
- Authentication
- Payments and subscriptions

This structure is designed to be MVP-ready while remaining extensible.

---

## 1. DiscoveryController

Handles all business discovery flows.

### Responsibilities
- Keyword search
- Category-based browsing
- Location-based browsing
- Filtering (rating, verified, open status)
- Sorting (relevance, rating, distance)
- Sponsored ordering
- Pagination

### Methods
- `index()`

### Used for
- Search results
- Category views
- Location views
- Combined category + location views

---

## 2. BusinessController

Handles single business profile pages.

### Responsibilities
- Load business core details
- Load category and location context
- Provide rating summary
- Load services and products
- Expose contact actions

### Methods
- `show(string $slug)`

---

## 3. ReviewController

Handles public review interactions.

### Responsibilities
- Fetch and paginate reviews
- Sort reviews (newest, highest, helpful)
- Submit new reviews
- Handle helpful / not helpful voting

### Methods
- `index(int $businessId)`
- `store(int $businessId)`
- `vote(int $reviewId)`

---

## 4. LeadController

Handles all contact and inquiry actions.

### Responsibilities
- Accept lead submissions
- Validate dynamic form fields
- Handle optional file uploads
- Return AJAX-friendly responses

### Methods
- `store(int $businessId)`

---

## 5. PhotoController

Handles business media and galleries.

### Responsibilities
- Fetch business photos
- Paginate photo galleries
- Accept photo uploads (optional at MVP)

### Methods
- `index(int $businessId)`
- `store(int $businessId)`

---

## 6. FilterController

Provides filter metadata for frontend use.

### Responsibilities
- Return categories
- Return locations
- Return rating thresholds
- Return attribute options

### Methods
- `index()`

---

## 7. MapController (Optional)

Supports map-based business discovery.

### Responsibilities
- Return lightweight geo data
- Provide minimal business info for map pins

### Methods
- `index()`

---

## Design Rules

- Controllers remain thin
- Business logic lives outside controllers
- All write actions use AJAX
- No session dependency
- Pagination is required on all lists

---

## Data Expectations

Each business payload should include:
- Name and slug
- Primary category
- Location
- Rating and review count
- Verification and sponsorship flags
- Contact action data

---

## Summary

This controller structure supports:
- Fast discovery
- Trust via reviews
- Strong conversion through contact actions

The architecture is intentionally simple, scalable, and safe for iteration.
