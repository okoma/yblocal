# Referral System Implementation Plan

This document breaks down the implementation of the Referral System Specification into ordered, dependency-aware tasks. It aligns with the existing codebase (business-scoped wallets, ActivationService, PaymentService, Filament panels). The spec is the “Referral System Specification for Business Listing Platform” (customer 10% cash commission, business referral credits + conversion).

---

## 1. Data model & migrations

### 1.1 Customer referral flow (Customer → Business)

| Task | Description |
|------|-------------|
| **1.1.1** | Create `customer_referrals` table: `id`, `referrer_user_id` (FK users, the customer), `referred_business_id` (FK businesses), `referral_code` (string, the code used), `status` enum (`pending`, `qualified` — first payment made), `created_at`, `updated_at`. Unique on `referred_business_id` (one referrer per business). |
| **1.1.2** | Create `customer_referral_wallets` table: `id`, `user_id` (FK users, unique — one per customer), `balance` (decimal), `currency` (default NGN), `created_at`, `updated_at`. |
| **1.1.3** | Create `customer_referral_transactions` table: `id`, `customer_referral_wallet_id`, `customer_referral_id` (nullable, link to the referral), `transaction_id` (nullable, FK transactions — source payment), `amount`, `type` enum (`commission`, `withdrawal`, `adjustment`), `balance_before`, `balance_after`, `description`, `reference_type`, `reference_id`, `created_at`. For audit and “pending vs completed” display. |
| **1.1.4** | Create `customer_referral_withdrawals` table: `id`, `user_id`, `customer_referral_wallet_id`, `amount`, `bank_name`, `account_name`, `account_number`, `sort_code` (optional), `status` enum (`pending`, `approved`, `rejected`), `processed_by` (nullable FK users), `processed_at`, `rejection_reason`, `notes`, `created_at`, `updated_at`. Keeps withdrawal flow separate from business `WithdrawalRequest`. |

### 1.2 Business referral flow (Business → Business)

| Task | Description |
|------|-------------|
| **1.2.1** | Create `business_referrals` table: `id`, `referrer_business_id` (FK businesses), `referred_business_id` (FK businesses), `referral_code` (string), `referral_credits_awarded` (integer, default 0), `status` enum (`pending`, `credited` — sign-up or first action done), `created_at`, `updated_at`. Unique on `referred_business_id`. |
| **1.2.2** | Add `referral_credits` (integer, default 0) to `businesses` table (or create `business_referral_credits` one row per business). Prefer column on `businesses` for simplicity. |
| **1.2.3** | Create `business_referral_credit_transactions` table: `id`, `business_id`, `business_referral_id` (nullable), `amount` (signed: +earned, -converted), `type` enum (`earned`, `converted_to_ad_credits`, `converted_to_quote_credits`, `converted_to_subscription`, `adjustment`), `balance_after`, `description`, `reference_type`, `reference_id`, `created_at`. For history and conversions. |

### 1.3 Referral codes and legacy

| Task | Description |
|------|-------------|
| **1.3.1** | Ensure every user has a unique `referral_code`: migration to backfill `users.referral_code` where null (e.g. `strtoupper(Str::random(10))` with uniqueness check). |
| **1.3.2** | Add `referral_code` (string, unique, nullable) to `businesses` table. Backfill from owner’s `user.referral_code` or generate per business. Used for business→business links (e.g. `?ref=BUSINESS_CODE`). |
| **1.3.3** | Decide fate of existing `referrals` table: keep for admin-only legacy data or migrate to new tables and deprecate. Recommendation: keep read-only for history; new flows use `customer_referrals` and `business_referrals`. |

---

## 2. Models & relationships

| Task | Description |
|------|-------------|
| **2.1** | **CustomerReferral** model: `referrer` → User, `referredBusiness` → Business; scopes `pending`, `qualified`; accessor for “referred business name”. |
| **2.2** | **CustomerReferralWallet** model: `user` → User; `transactions` → CustomerReferralTransaction; `withdrawals` → CustomerReferralWithdrawal; `deposit($amount, $description, $reference)` and `withdraw($amount, ...)` updating balance and creating `CustomerReferralTransaction`. |
| **2.3** | **CustomerReferralTransaction** model: wallet, optional customer_referral_id, optional transaction_id (source payment), type, amount, balance_before/after, description, polymorphic reference. |
| **2.4** | **CustomerReferralWithdrawal** model: user, customer_referral_wallet; status; `approve(User)`, `reject(User, reason)`; scopes pending/approved/rejected. |
| **2.5** | **BusinessReferral** model: `referrerBusiness` → Business, `referredBusiness` → Business; scopes pending/credited. |
| **2.6** | **BusinessReferralCreditTransaction** model: business, optional business_referral_id, amount (signed), type, balance_after, description, polymorphic reference. |
| **2.7** | **User**: `referral_code` accessor/mutator if needed; `customerReferralWallet()` hasOne; `customerReferrals()` hasMany (as referrer). |
| **2.8** | **Business**: `referral_credits` attribute; `referralCode`; `businessReferralsAsReferrer()` hasMany; `businessReferralAsReferred()` hasOne; `referralCreditTransactions()` hasMany. |

---

## 3. Referral sign-up & linking

| Task | Description |
|------|-------------|
| **3.1** | **Customer referral link**: URL format e.g. `https://yellowbooks.ng/register?ref=USER_REFERRAL_CODE` (customer panel domain) or business signup URL with `ref=USER_REFERRAL_CODE`. Resolve referrer by `User::where('referral_code', $code)->where('role', 'customer')->first()`. Only accept if user is customer and (optional) verified. |
| **3.2** | **Business registration (Filament)** with ref param: On business panel register page, accept `ref` query param. Store in session or hidden field. On User creation (business owner), do not create referral yet; create when **Business** is created (so we have `referred_business_id`). So: when CreateBusiness runs, check session/request for `ref`; if present and code belongs to a **customer**, create `CustomerReferral` (referrer_user_id, referred_business_id, referral_code, status=pending). If code belongs to a **business**, create `BusinessReferral` (referrer_business_id, referred_business_id, referral_code, status=pending) and award referral credits to referrer business (see 4.2). |
| **3.3** | **Business referral link**: Format e.g. `https://biz.yellowbooks.ng/register?ref=BUSINESS_REFERRAL_CODE`. Resolve referrer by `Business::where('referral_code', $code)->first()`. When new business is created with this ref, create `BusinessReferral` and award credits (see 4.2). |
| **3.4** | **Fraud / rules**: Ensure referred_business_id is new (not already linked); one CustomerReferral per business; one BusinessReferral per business; optional: same IP / same user check to block self-referral. |

---

## 4. Commission & credit logic

### 4.1 Customer commission (10% after payment)

| Task | Description |
|------|-------------|
| **4.1.1** | Create **ReferralCommissionService** (or extend ActivationService): method `processCustomerCommission(Transaction $transaction)`. Called after `activatePayable()` in ActivationService. |
| **4.1.2** | In `processCustomerCommission`: Get `business_id` from transaction (from transactionable: Subscription, AdCampaign, Wallet). Find `CustomerReferral` where `referred_business_id` = that business and status is pending or qualified. If none, return. Compute commission = 10% of `$transaction->amount`. Get or create CustomerReferralWallet for referrer user; credit commission (deposit); create CustomerReferralTransaction (type=commission, reference=transaction). Mark CustomerReferral as `qualified` if first payment. Idempotency: e.g. skip if CustomerReferralTransaction already exists for this transaction_id. |
| **4.1.3** | Wire into **ActivationService**: at end of `activatePayable()`, call `ReferralCommissionService::processCustomerCommission($transaction)` (or dispatch job for async). |
| **4.1.4** | Cover all payable types that represent “business paid”: Subscription, AdCampaign, Wallet (when wallet is business’s — funding, ad credit purchase, quote credit purchase). Ensure transaction has business_id in all cases (already does via payable). |

### 4.2 Business referral credits (on sign-up)

| Task | Description |
|------|-------------|
| **4.2.1** | Define default “referral credits per sign-up” (e.g. config `referral.business_credits_per_signup` = 100). |
| **4.2.2** | When **BusinessReferral** is created (new business signed up with business ref code): increment referrer business’s `referral_credits` by configured amount; create `BusinessReferralCreditTransaction` (type=earned, amount=+X, balance_after=new balance); set BusinessReferral status to `credited`. |
| **4.2.3** | **Conversion**: Create **ReferralCreditConversionService** (or methods on Business model): `convertToAdCredits($credits)`, `convertToQuoteCredits($credits)`, `convertToSubscription($credits)` (1-month). Each: deduct from `business.referral_credits`; add to wallet `ad_credits` / `quote_credits` or create/extend subscription; create `BusinessReferralCreditTransaction` (type=converted_*, amount negative, reference to wallet/subscription). Define conversion rates (e.g. 1 referral credit = 1 ad credit, 1 quote credit; 1-month sub = X credits). |

---

## 5. Withdrawals (customer referral commission)

| Task | Description |
|------|-------------|
| **5.1** | **CustomerReferralWithdrawal**: validation (amount <= wallet balance, min amount if any). On create, deduct from CustomerReferralWallet (or reserve) and create CustomerReferralTransaction (type=withdrawal) when status becomes approved. |
| **5.2** | **Admin**: List/approve/reject customer referral withdrawals (Filament resource or relation manager). On approve: mark withdrawal approved, create payout record (or mark “paid”); optionally integrate with payout API later. On reject: refund balance (add back to CustomerReferralWallet) and create adjustment transaction. |
| **5.3** | Optional: minimum withdrawal amount and processing fee in config. |

---

## 6. Dashboards (Filament)

### 6.1 Customer referral dashboard

| Task | Description |
|------|-------------|
| **6.1.1** | **Referrals** page in Customer panel: “Invite Businesses & Earn” or “Referrals”. Sections: (1) Referral link + copy button + share buttons (WhatsApp, Twitter, etc.); display user’s referral_code and full URL. (2) Summary: total referred, pending commissions, completed commissions, balance (from CustomerReferralWallet). (3) List of referred businesses (from CustomerReferral): business name, sign-up date, status (pending/qualified), total commission earned for that referral. (4) Earnings graph/trend: total commission over time (use CustomerReferralTransaction type=commission, group by month). (5) Withdrawal: form (amount, bank_name, account_name, account_number) and list of pending/approved/rejected withdrawals. |
| **6.1.2** | Ensure referral link uses customer panel base URL or main site register URL with ref= code. |

### 6.2 Business referral dashboard

| Task | Description |
|------|-------------|
| **6.2.1** | **Referrals** page in Business panel: (1) Referral link + copy + share (business’s referral_code, link to business signup with ref=). (2) Referral credit balance (business.referral_credits). (3) List of referred businesses (BusinessReferral): business name, sign-up date, status, credits awarded. (4) Conversion: buttons/forms to “Convert to Ad Credits”, “Convert to Quote Credits”, “Convert to 1-Month Subscription” with amounts and confirmation. (5) History of conversions (BusinessReferralCreditTransaction). (6) Simple trend: credits earned over time. |

---

## 7. Admin & config

| Task | Description |
|------|-------------|
| **7.1** | **Admin panel**: (1) List CustomerReferral (referrer, referred business, status, total commission). (2) List CustomerReferralWallet (user, balance). (3) List CustomerReferralWithdrawal (approve/reject). (4) List BusinessReferral. (5) Optional: config page or env for commission rate (10%), business sign-up credits, conversion rates. |
| **7.2** | **Config**: Add `config/referral.php`: `customer_commission_rate` (0.10), `business_credits_per_signup`, `conversion_to_ad_credits_ratio`, `conversion_to_quote_credits_ratio`, `conversion_to_subscription_credits` (credits needed for 1-month sub). |

---

## 8. Notifications (optional but recommended)

| Task | Description |
|------|-------------|
| **8.1** | When a business signs up via customer referral: notify customer “A business you referred just joined.” |
| **8.2** | When referred business makes first payment: notify customer “You earned X commission from [Business].” |
| **8.3** | When a business signs up via business referral: notify referring business “You earned X referral credits.” |
| **8.4** | When customer referral withdrawal is approved/rejected: email customer. |

---

## 9. Implementation order (dependency summary)

1. **Migrations (1.1, 1.2, 1.3)** — run in order; 1.3.1 and 1.3.2 can run after 1.1/1.2.
2. **Models & relations (2.x)** — after tables exist.
3. **ReferralCommissionService + ActivationService hook (4.1)** — so commission is paid on every payment.
4. **Business referral sign-up and credit award (3.2, 3.3, 4.2)** — so business ref flow works.
5. **Customer referral linking at business creation (3.1, 3.2)** — so customer ref link creates CustomerReferral.
6. **Conversion service (4.2.3)** — business credits → ad/quote/subscription.
7. **Customer referral dashboard (6.1)** and **withdrawal flow (5)**.
8. **Business referral dashboard (6.2)**.
9. **Admin resources (7.1)** and **config (7.2)**.
10. **Notifications (8)** last.

---

## 10. Files to add/change (checklist)

| Area | New files | Files to change |
|------|-----------|------------------|
| Migrations | 1.1.1–1.1.4, 1.2.1–1.2.3, 1.3.1–1.3.2 | — |
| Models | CustomerReferral, CustomerReferralWallet, CustomerReferralTransaction, CustomerReferralWithdrawal, BusinessReferral, BusinessReferralCreditTransaction | User, Business |
| Services | ReferralCommissionService, ReferralCreditConversionService | ActivationService |
| Registration / Business create | — | Customer Panel Register (accept ref), Business Panel Register + CreateBusiness (accept ref, create CustomerReferral/BusinessReferral) |
| Customer panel | Referrals page (Filament Page + view) | CustomerPanelProvider (route) |
| Business panel | Referrals page | BusinessPanelProvider |
| Admin | CustomerReferralResource, CustomerReferralWalletResource, CustomerReferralWithdrawalResource, BusinessReferralResource (or reuse/extend existing ReferralResource) | — |
| Config | config/referral.php | — |
| Notifications | Optional: ReferralSignUpNotification, CommissionEarnedNotification, etc. | — |

---

This plan keeps customer commission (10%) and business referral credits (earned on sign-up, converted to ads/quote/subscription) clearly separated, reuses your existing payment completion path (ActivationService), and adds minimal new concepts (customer referral wallet + business referral_credits). You can implement phase by phase (e.g. 1–2 → 3–4 → 5–6 → 7–8) and test after each phase.
