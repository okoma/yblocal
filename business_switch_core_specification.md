# Active Business Switch – Core Specification

## Purpose
The Active Business Switch establishes **business context** across the entire platform. At any moment, the application behaves as if the user is managing **one business only**. All data access, UI, limits, analytics, and billing logic are scoped to the active business.

This system must work **before** introducing services, booking, or advanced plans.

---

## Core Principle
> **Nothing in the app is global. Everything belongs to a business.**

The user does not interact with data directly. The user always interacts *through* an active business.

---

## Key Definitions

### Business
A business is the primary ownership unit.
- Owns data (products, leads, reviews, analytics, transactions)
- Owns subscription plans
- Owns limits and feature access

### Active Business
The currently selected business that defines context.
- Stored in session as `active_business_id`
- Required for all authenticated routes
- Switching business changes the entire app state

---

## Non‑Goals (Explicitly Out of Scope)
- Booking system
- Services table
- Complex permissions
- Feature monetization logic

This document defines **context only**, not features.

---

## Data Model Requirements

### Businesses Table
Required fields:
- id
- owner_user_id
- business_type_id (future use)
- plan_id
- status

### Business Ownership
A user may own multiple businesses.
All other entities must reference a business.

---

## Business‑Scoped Tables
All core tables must include:

```
business_id (nullable initially, required going forward)
```

Tables affected:
- products
- leads
- reviews
- analytics
- transactions
- subscriptions / plans (business‑linked)
- any model thats business related

No table may be queried without filtering by business_id once the switch is active.

---

## Active Business State

### Storage
- Stored in session: `active_business_id`
- Set when user selects a business
- Persisted across requests

### Validation Rules
- Active business must belong to authenticated user
- If missing or invalid → redirect to business selector

---


## Business Switch UI Behavior
On switch:
  * Update session via Livewire action
  * Emit 'business-switched' Livewire event
  * All components refresh reactively (NO page reload)

Implementation:
  * Business switcher emits event: $this->dispatch('business-switched')
  * All Filament resources/widgets listen: protected $listeners = ['business-switched' => '$refresh']
  * Session still stores active_business_id (for middleware/global scope)

No URL change required.

---

## Sidebar Behavior
The sidebar must be **business‑aware**.

Rules:
- Sidebar reads active business
- Sidebar items are filtered based on business context
- Labels and visibility may change later via business type

At this stage:
- Same features
- Different data per business

---

## Query Rules (Critical)

Every query must:
- Resolve active business first
- Filter by `business_id = active_business_id`

No exceptions.

If data appears across businesses, the switch is broken.

---

## Analytics & Metrics

Analytics are calculated per business.

Switching business must:
- Change dashboard numbers
- Change charts
- Change reports

No global aggregation.

---

## Subscription & Plan Context

Plans are attached to **business**, not user.

Rules:
- Each business has its own plan
- Limits apply per business
- Switching business switches plan context

Plan enforcement logic comes later.

---

## Migration Strategy

1. Add `business_id` (nullable) to all core tables
2. Assign existing records to a default business where possible
3. Enforce business_id on all new records
4. Gradually remove nullable state

---

## Success Criteria
The business switch is considered complete when:
- Switching business updates sidebar
- Switching business updates lists
- Switching business updates analytics
- No data leaks across businesses
- Routes do not need business identifiers

---

## Architectural Outcome
Once implemented:
- All future features inherit business context automatically
- Booking, services, and monetization become plug‑ins, not rewrites
- The system becomes predictable for humans and AI agents

---

## Guiding Rule (Never Break This)
> **If active business is unclear, the app must stop.**

This rule protects the entire platform architecture.

