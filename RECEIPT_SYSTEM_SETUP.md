# 📄 Receipt System Setup Guide

## Overview
Transaction receipt generation system with PDF download capability for completed payments (Subscriptions, Ad Campaigns, Wallet Funding).

---

## 🚀 Installation Steps

### 1. Install the PDF Generation Library

```bash
composer require barryvdh/laravel-dompdf
```

### 2. Publish Configuration (Optional)

```bash
php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
```

This will create `config/dompdf.php` where you can customize PDF settings like:
- Paper size (default: A4)
- Orientation (portrait/landscape)
- Encoding
- Font directory

---

## 📁 Files Created/Modified

### ✅ Routes
**File:** `routes/web.php`
- Route: `GET /business/transaction/{transaction}/receipt`
- Name: `business.transaction.receipt`
- Auth: Required
- Controller: `PaymentController@downloadReceipt`

### ✅ Controller Method
**File:** `app/Http/Controllers/PaymentController.php`
- Method: `downloadReceipt(Transaction $transaction)`
- Authorization: Ensures user owns the transaction or the associated business
- Validation: Only allows receipt for completed transactions
- Returns: PDF download with filename format: `receipt-{reference}.pdf`

### ✅ Receipt Template
**File:** `resources/views/receipts/transaction.blade.php`
- Professional PDF layout
- Company branding
- Transaction details
- Customer information
- Payment breakdown
- Dynamic content based on payment type (Subscription/Ad Campaign/Wallet)

### ✅ Filament Resource
**File:** `app/Filament/Business/Resources/TransactionResource.php`
- Added "Receipt" button in table actions
- Visible only for completed transactions
- Opens in new tab

---

## 🎨 Receipt Features

### Design
- ✅ Professional, clean layout
- ✅ Company branding (YellowBooks Nigeria)
- ✅ Status badge (Completed)
- ✅ Color-coded sections
- ✅ Responsive grid layout

### Information Displayed
1. **Transaction Details**
   - Receipt number (reference)
   - Transaction date
   - Payment method (Paystack/Flutterwave/etc.)
   - Payment type (Subscription/Campaign/Wallet)

2. **Customer Information**
   - Customer name
   - Email address

3. **Payment Breakdown**
   - Subtotal
   - Discount (if applied)
   - Total amount paid

4. **Type-Specific Details**
   - **Subscription**: Plan name, billing cycle, start/end dates, business name
   - **Ad Campaign**: Campaign name, type, start/end dates, business name
   - **Wallet**: Funding amount

---

## 🔒 Security Features

### Authorization
```php
// Checks if user owns the transaction OR owns the associated business
$isOwner = $transaction->user_id === $user->id;

// For subscriptions and campaigns, also check business ownership
if ($subscription->business_id) {
    $isOwner = $isOwner || ($business->user_id === $user->id);
}
```

### Validation
- ✅ Only authenticated users
- ✅ Only transaction owners or business owners
- ✅ Only completed transactions (no pending/failed)
- ✅ Returns 403 for unauthorized access

---

## 📝 Usage

### From Business Panel
1. Navigate to **Transactions** page
2. Find a completed transaction
3. Click the **Receipt** button (download icon)
4. PDF will download automatically

### Filename Format
```
receipt-TXN-20260122-XXXXXX.pdf
```
Based on the transaction reference number.

---

## 🎯 Customization Options

### 1. Company Information
Edit in `resources/views/receipts/transaction.blade.php`:
```blade
<h1>YellowBooks Nigeria</h1>
<p>Nigeria's #1 Business Directory Platform</p>
<p>Email: support@yellowbooks.ng | Phone: +234 XXX XXX XXXX</p>
```

### 2. Add Company Logo
```blade
<div class="header">
    <img src="{{ public_path('images/logo.png') }}" alt="Logo" style="max-width: 150px; margin-bottom: 20px;">
    <h1>YellowBooks Nigeria</h1>
    ...
</div>
```

### 3. PDF Settings
Edit `config/dompdf.php` (after publishing):
```php
'paper' => 'a4',        // Paper size
'orientation' => 'portrait',  // portrait or landscape
'defines' => [
    'font_dir' => storage_path('fonts/'),
    'font_cache' => storage_path('fonts/'),
    'temp_dir' => sys_get_temp_dir(),
    'dpi' => 96,
    'enable_php' => false,
    'enable_javascript' => false,
    'enable_remote' => true,
],
```

### 4. Add Tax Information
```blade
<div class="amount-row">
    <span class="amount-label">VAT (7.5%)</span>
    <span class="amount-value">₦{{ number_format($transaction->amount * 0.075, 2) }}</span>
</div>
```

---

## 🧪 Testing

### Test Receipt Download
```bash
# Start the server
php artisan serve

# Login to business panel
# Create a test transaction (complete a payment)
# Click the Receipt button
```

### Test Authorization
- ✅ User A cannot download User B's receipts
- ✅ Pending transactions don't show receipt button
- ✅ Failed transactions don't show receipt button

---

## 🐛 Troubleshooting

### Issue: "Class 'Barryvdh\DomPDF\Facade\Pdf' not found"
**Solution:** Run `composer require barryvdh/laravel-dompdf`

### Issue: PDF shows broken layout
**Solution:** Use inline CSS only (no external stylesheets)

### Issue: Images not showing in PDF
**Solution:** Use absolute paths with `public_path()`
```blade
<img src="{{ public_path('images/logo.png') }}" />
```

### Issue: Fonts not rendering
**Solution:** Use web-safe fonts or configure custom fonts in `config/dompdf.php`

---

## 📊 Future Enhancements

- [ ] Add company logo upload in GeneralSettings
- [ ] Add receipt prefix configuration
- [ ] Add tax ID/VAT number configuration
- [ ] Email receipt to customer automatically
- [ ] Generate receipt for refunds
- [ ] Multi-language support
- [ ] Receipt numbering system
- [ ] Bulk receipt download
- [ ] Receipt preview before download

---

## 🎉 All Done!

The receipt system is now fully functional. Just install the library:

```bash
composer require barryvdh/laravel-dompdf
```

Then test it out by completing a payment and clicking the Receipt button! 🚀
