Feature: Quote Requests (Reverse-Quote Marketplace)
1. Entities / Data Model

quote_requests

id

business_id → optional (if pre-assigned, otherwise null)

category_id

location_id

title

description

budget_min (optional)

budget_max (optional)

status (open, closed, expired)

expires_at

created_at, updated_at

quote_responses

id

quote_request_id

business_id ✅

price

delivery_time

message (short proposal)

status (submitted, shortlisted, accepted, rejected)

created_at, updated_at

quote_credits

id

business_id

quote_request_id

quate_credits_used

created_at

Use wallet table for quote credits.

2. Business Logic

Customer flow

Fills in quote request form

Sets optional budget, timeline, attachments

Submits → system marks as open and distributes

Distribution

Only eligible businesses see the request:

category match

location match

plan or quote credits available

Limit: e.g., max 5–10 businesses per quote

Business flow

Submits quote → consumes 1 credit (wallet deduct or plan)

Status changes to submitted

Cannot see competitors’ prices

Optional: attach documents

Customer selection

Sees all submitted quotes

Can shortlist, accept, or reject

Upon acceptance, optional invoice can be issued

Quote request status updated to closed or accepted

3. Monetization / Wallet Integration

Quote credits = paid action

Wallet deduction or plan consumption occurs when business submits a quote

Transaction recorded in wallet_transactions

Optionally show daily / monthly quote limits for free or paid plans

4. UI / UX Rules

Customers: simple form, clear “Request a Quote” button, comparison table for quotes

Businesses: dashboard shows quote requests available, remaining credits, submit button, no competitor info

Notifications: optional email / dashboard alerts for new requests and responses

Expiration: request expires automatically if no response within defined timeframe

5. Rules / Constraints

Every quote request belongs to exactly one category and location

Every quote response belongs to exactly one business

Credit deduction is mandatory for quote submission

Prevent unlimited submissions to maintain scarcity and quality

Track all actions for audit and analytics

Business wallet and transactions already exist → integrate seamlessly