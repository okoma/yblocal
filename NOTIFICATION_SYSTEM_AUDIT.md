# 🔍 Notification System Audit & Implementation Plan

**Date:** January 27, 2026  
**Status:** ⚠️ **INCOMPLETE** - Notifications configured but not implemented

---

## 📊 **Current State Analysis**

### ✅ **What's Already Built**

#### **1. Database Infrastructure**
- ✅ `notifications` table created with UUID support
- ✅ Predefined notification types in migration
- ✅ Polymorphic structure (notifiable)
- ✅ `user_preferences` table with notification toggles
- ✅ Customer & Business Owner preference fields

#### **2. Preference System**
- ✅ Business Owner preferences page (`/business/account-preferences`)
  - Email notifications (6 types)
  - Telegram notifications (6 types with chat_id/username)
  - WhatsApp notifications (2 types with verification)
- ✅ Customer preferences page (`/customer/notification-preferences`)
  - Email notifications (5 types)
  - In-app notifications (4 types)

#### **3. Existing Email Infrastructure**
- ✅ One Mailable class: `ManagerInvitationMail`
- ✅ One email template: `emails/manager-invitation.blade.php`
- ✅ Mail config properly structured
- ✅ Queue configured (database driver)

#### **4. Filament In-App Notifications**
- ✅ Used in multiple places (success/error messages)
- ✅ Database notifications enabled in CustomerPanelProvider
- ✅ Notification polling every 30s

---

## ❌ **What's Missing (Critical)**

### **1. NO Laravel Notification Classes**
```
❌ app/Notifications/ directory is EMPTY
```

**Needed:**
- `ReviewReplyNotification` - When business replies to customer's review
- `InquiryResponseNotification` - When business responds to customer's inquiry  
- `NewLeadNotification` - When business receives a new lead
- `NewReviewNotification` - When business receives a new review
- `BusinessUpdateNotification` - Updates from saved businesses
- `PromotionalNotification` - Special offers
- `NewsletterNotification` - Platform updates
- `VerificationStatusNotification` - Verification approved/rejected
- `PremiumExpiringNotification` - Premium subscription expiring

### **2. NO Notification Triggers**
```php
// ❌ LeadController.php (line 61-62)
// TODO: Send notification email to business owner
// TODO: Send confirmation email to lead submitter
```

**Missing triggers in:**
- Review submissions (no notification to business owner)
- Lead submissions (no notification to business owner)
- Review replies (no notification to customer)
- Lead replies (no notification to customer)
- Business claim approvals
- Verification status changes

### **3. NO Email Templates**
```
❌ Only 1 email template exists: manager-invitation.blade.php
```

**Needed templates:**
- `emails/review-reply.blade.php` (customer)
- `emails/inquiry-response.blade.php` (customer)
- `emails/new-lead.blade.php` (business owner)
- `emails/new-review.blade.php` (business owner)
- `emails/business-update.blade.php` (customer)
- `emails/promotional.blade.php` (customer)
- `emails/verification-approved.blade.php` (business owner)
- `emails/premium-expiring.blade.php` (business owner)

### **4. SMTP Not Configured**
```env
# Current (.env.example)
MAIL_MAILER=log  # ❌ Only logs emails, doesn't send them
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_FROM_ADDRESS="hello@example.com"  # ❌ Placeholder
```

**Need to configure:**
- Real SMTP credentials
- Or email service (Mailgun, SendGrid, etc.)

---

## 🎯 **Implementation Priority**

### **PHASE 1: Critical Customer Notifications** ⚡
**Priority: HIGH** - These directly impact customer experience

1. **Review Reply Notification** (Business → Customer)
   - Trigger: When business owner adds/updates `reply` field in review
   - Check: `user_preferences.notify_review_reply_received`
   - Send: Email + In-app notification

2. **Inquiry Response Notification** (Business → Customer)
   - Trigger: When business owner updates lead with `reply_message`
   - Check: `user_preferences.notify_inquiry_response_received`
   - Send: Email + In-app notification

### **PHASE 2: Business Owner Notifications** ⚡
**Priority: HIGH** - Critical for business operations

3. **New Lead Notification** (Customer → Business)
   - Trigger: When customer submits inquiry (`LeadController@store`)
   - Check: `user_preferences.notify_new_leads`
   - Send: Email + Telegram + WhatsApp (based on preferences)

4. **New Review Notification** (Customer → Business)
   - Trigger: When customer submits review (`ReviewController@store`)
   - Check: `user_preferences.notify_new_reviews`
   - Send: Email + Telegram + WhatsApp (based on preferences)

### **PHASE 3: System Notifications** 📋
**Priority: MEDIUM** - Important but not urgent

5. **Verification Status** (Admin → Business)
   - Approved/Rejected notifications

6. **Premium Expiring** (System → Business)
   - 7 days before, 3 days before, 1 day before

7. **Campaign Updates** (System → Business)
   - Campaign ending soon

### **PHASE 4: Marketing Notifications** 📢
**Priority: LOW** - Nice to have

8. **Business Updates** (Business → Saved Customers)
9. **Promotional Offers** (System → Customers)
10. **Newsletter** (System → All Users)

---

## 🔧 **SMTP Configuration Options**

### **Option 1: Gmail SMTP** (Free, Easy Setup)
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password  # Generate in Google Account
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="YellowBooks Nigeria"
```

**Pros:** Free, easy setup  
**Cons:** 500 emails/day limit, requires 2FA + app password

### **Option 2: Mailtrap** (Development/Staging)
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-mailtrap-username
MAIL_PASSWORD=your-mailtrap-password
MAIL_FROM_ADDRESS=hello@yellowbooks.ng
MAIL_FROM_NAME="YellowBooks Nigeria"
```

**Pros:** Perfect for testing, catches all emails  
**Cons:** Not for production

### **Option 3: Mailgun** (Recommended for Production)
```env
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=mg.yellowbooks.ng
MAILGUN_SECRET=your-api-key
MAILGUN_ENDPOINT=api.mailgun.net
MAIL_FROM_ADDRESS=noreply@yellowbooks.ng
MAIL_FROM_NAME="YellowBooks Nigeria"
```

**Pros:** Reliable, 10k free emails/month, good deliverability  
**Cons:** Requires domain setup

### **Option 4: SendGrid** (Alternative)
```bash
composer require symfony/sendgrid-mailer
```

```env
MAIL_MAILER=sendgrid
SENDGRID_API_KEY=your-sendgrid-api-key
MAIL_FROM_ADDRESS=noreply@yellowbooks.ng
MAIL_FROM_NAME="YellowBooks Nigeria"
```

**Pros:** 100 emails/day free, excellent deliverability  
**Cons:** Requires account setup

### **Option 5: Amazon SES** (Enterprise)
```env
MAIL_MAILER=ses
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1
MAIL_FROM_ADDRESS=noreply@yellowbooks.ng
```

**Pros:** Cheap ($0.10 per 1000 emails), highly scalable  
**Cons:** More complex setup

---

## 📝 **Step-by-Step Implementation Guide**

### **STEP 1: Configure SMTP** (Start with Mailtrap for testing)

```bash
# 1. Sign up at https://mailtrap.io
# 2. Get your credentials
# 3. Update .env file

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@yellowbooks.ng"
MAIL_FROM_NAME="YellowBooks Nigeria"
```

### **STEP 2: Create Notification Classes**

```bash
# Create all notification classes
php artisan make:notification ReviewReplyNotification
php artisan make:notification InquiryResponseNotification
php artisan make:notification NewLeadNotification
php artisan make:notification NewReviewNotification
php artisan make:notification BusinessUpdateNotification
php artisan make:notification PromotionalNotification
php artisan make:notification NewsletterNotification
php artisan make:notification VerificationApprovedNotification
php artisan make:notification VerificationRejectedNotification
php artisan make:notification PremiumExpiringNotification
```

### **STEP 3: Create Email Templates**

Create in `resources/views/emails/`:
- `review-reply.blade.php`
- `inquiry-response.blade.php`
- `new-lead.blade.php`
- `new-review.blade.php`
- `business-update.blade.php`
- `promotional.blade.php`
- `newsletter.blade.php`
- `verification-approved.blade.php`
- `verification-rejected.blade.php`
- `premium-expiring.blade.php`

### **STEP 4: Implement Notification Triggers**

#### **A. Review Reply (Customer notification)**
In `app/Filament/Business/Resources/ReviewResource.php` or model observer:

```php
// After review reply is saved
if ($review->wasChanged('reply') && !empty($review->reply)) {
    $review->update(['replied_at' => now()]);
    
    $customer = $review->user;
    if ($customer && $customer->preferences->notify_review_reply_received) {
        $customer->notify(new ReviewReplyNotification($review));
    }
}
```

#### **B. Inquiry Response (Customer notification)**
In `app/Filament/Business/Resources/LeadResource.php` or model observer:

```php
// After lead reply is saved
if ($lead->wasChanged('reply_message') && $lead->is_replied) {
    $lead->update(['replied_at' => now()]);
    
    $customer = $lead->user;
    if ($customer && $customer->preferences->notify_inquiry_response_received) {
        $customer->notify(new InquiryResponseNotification($lead));
    }
}
```

#### **C. New Lead (Business owner notification)**
In `app/Http/Controllers/LeadController.php` (line 61):

```php
// Replace TODO with actual notification
$businessOwner = $business->user;
$preferences = $businessOwner->preferences;

// Email
if ($preferences->notify_new_leads) {
    $businessOwner->notify(new NewLeadNotification($lead));
}

// Telegram
if ($preferences->notify_new_leads_telegram && $preferences->telegram_chat_id) {
    // Send via Telegram API
}

// WhatsApp
if ($preferences->notify_new_leads_whatsapp && $preferences->whatsapp_verified) {
    // Send via WhatsApp API
}
```

#### **D. New Review (Business owner notification)**
In `app/Http/Controllers/ReviewController.php` after review is created:

```php
$businessOwner = $business->user;
$preferences = $businessOwner->preferences;

if ($preferences->notify_new_reviews) {
    $businessOwner->notify(new NewReviewNotification($review));
}
```

### **STEP 5: Test Notifications**

```bash
# 1. Start queue worker
php artisan queue:work

# 2. Test in tinker
php artisan tinker

# Test review reply notification
$review = Review::first();
$customer = $review->user;
$customer->notify(new App\Notifications\ReviewReplyNotification($review));

# Check Mailtrap inbox - you should see the email!
```

---

## 🎨 **Email Template Structure**

### **Standard Email Layout**
All emails should follow this structure:

```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <!-- Header with logo -->
    <div style="background: #f59e0b; padding: 20px; text-align: center;">
        <h1 style="color: white; margin: 0;">YellowBooks Nigeria</h1>
    </div>
    
    <!-- Content -->
    <div style="padding: 30px; max-width: 600px; margin: 0 auto;">
        <h2>{{ $heading }}</h2>
        
        <p>{{ $greeting }}</p>
        
        <!-- Main content -->
        @yield('content')
        
        <!-- Call to action button -->
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $actionUrl }}" 
               style="background: #f59e0b; color: white; padding: 12px 30px; 
                      text-decoration: none; border-radius: 5px; display: inline-block;">
                {{ $actionText }}
            </a>
        </div>
        
        <p>{{ $footer }}</p>
    </div>
    
    <!-- Footer -->
    <div style="background: #f3f4f6; padding: 20px; text-align: center; font-size: 12px;">
        <p>© {{ date('Y') }} YellowBooks Nigeria. All rights reserved.</p>
        <p>
            <a href="{{ url('/customer/notification-preferences') }}">Manage Notifications</a> | 
            <a href="{{ url('/contact') }}">Contact Us</a>
        </p>
    </div>
</body>
</html>
```

---

## 🚨 **Common Issues & Solutions**

### **Issue 1: Emails not sending**
```bash
# Check queue
php artisan queue:work

# Check mail logs
tail -f storage/logs/laravel.log

# Test mail config
php artisan tinker
Mail::raw('Test', function($msg) { $msg->to('test@example.com')->subject('Test'); });
```

### **Issue 2: Notifications going to spam**
- ✅ Use a real domain (not gmail)
- ✅ Set up SPF, DKIM, DMARC records
- ✅ Use a dedicated email service (Mailgun, SendGrid)
- ✅ Avoid spam trigger words

### **Issue 3: Queue not processing**
```bash
# Make sure queue worker is running
php artisan queue:work

# Or use supervisor in production
# /etc/supervisor/conf.d/laravel-worker.conf
```

### **Issue 4: Preferences not being checked**
```php
// Always check preferences before sending
$preferences = $user->preferences;
if ($preferences->notify_review_reply_received) {
    // Send notification
}
```

---

## 📋 **Implementation Checklist**

### **Phase 1 - Setup** (Day 1)
- [ ] Configure SMTP (Mailtrap for testing)
- [ ] Update `.env` with mail credentials
- [ ] Test basic email sending
- [ ] Create notification classes (4 critical ones first)
- [ ] Create email templates (4 critical ones first)

### **Phase 2 - Critical Notifications** (Day 2-3)
- [ ] Implement ReviewReplyNotification
- [ ] Implement InquiryResponseNotification
- [ ] Implement NewLeadNotification
- [ ] Implement NewReviewNotification
- [ ] Add triggers in controllers/resources
- [ ] Test all 4 notifications

### **Phase 3 - System Notifications** (Day 4-5)
- [ ] Implement VerificationStatusNotification
- [ ] Implement PremiumExpiringNotification
- [ ] Implement CampaignUpdateNotification
- [ ] Add scheduled tasks for expiring notifications

### **Phase 4 - Marketing** (Day 6-7)
- [ ] Implement BusinessUpdateNotification
- [ ] Implement PromotionalNotification
- [ ] Implement NewsletterNotification
- [ ] Create admin interface for sending bulk notifications

### **Phase 5 - Production** (Day 8-9)
- [ ] Switch to production SMTP (Mailgun/SendGrid)
- [ ] Set up domain authentication (SPF, DKIM)
- [ ] Configure queue supervisor
- [ ] Set up monitoring (failed jobs, bounce rates)
- [ ] Test all notifications in production

---

## 💰 **Cost Estimates**

### **Email Services Pricing**

| Service | Free Tier | Paid Plans |
|---------|-----------|------------|
| **Gmail SMTP** | 500/day | Not for commercial |
| **Mailtrap** | Development only | $10/mo (1k emails) |
| **Mailgun** | 10k emails/mo | $35/mo (50k emails) |
| **SendGrid** | 100/day | $20/mo (40k emails) |
| **Amazon SES** | 62k/mo (EC2) | $0.10 per 1k emails |

**Recommendation for YellowBooks:**
- **Development**: Mailtrap (free)
- **Production**: Mailgun ($35/mo) or AWS SES ($0.10/1k)

---

## 🎯 **Success Metrics**

Track these metrics after implementation:

1. **Email Deliverability** (target: >95%)
2. **Open Rate** (target: >20%)
3. **Click-Through Rate** (target: >3%)
4. **Bounce Rate** (target: <5%)
5. **Unsubscribe Rate** (target: <2%)
6. **Notification Response Time** (target: <30 seconds)

---

## ✅ **Quick Start (Minimum Viable)**

If you want to get started ASAP with the minimum viable notification system:

```bash
# 1. Configure Mailtrap (5 minutes)
# Sign up at mailtrap.io, get credentials, update .env

# 2. Create 2 critical notifications (10 minutes)
php artisan make:notification ReviewReplyNotification
php artisan make:notification NewLeadNotification

# 3. Create 2 email templates (15 minutes)
# resources/views/emails/review-reply.blade.php
# resources/views/emails/new-lead.blade.php

# 4. Add triggers (20 minutes)
# Update LeadController@store line 61
# Add observer for Review model replies

# 5. Test (10 minutes)
php artisan queue:work
# Submit a lead, reply to review, check Mailtrap

# Total: ~60 minutes to get basic notifications working!
```

---

## 📚 **Resources**

- Laravel Notifications: https://laravel.com/docs/notifications
- Laravel Queues: https://laravel.com/docs/queues
- Mailtrap: https://mailtrap.io
- Mailgun: https://www.mailgun.com
- SendGrid: https://sendgrid.com
- Email Design Best Practices: https://www.emailonacid.com/blog/

---

## 🎉 **Summary**

### **Good News:**
- ✅ Infrastructure is ready (database, preferences)
- ✅ Preference system is complete
- ✅ One working email example exists
- ✅ Queue is configured

### **What Needs Doing:**
- ❌ Create notification classes (10 needed)
- ❌ Create email templates (10 needed)
- ❌ Add notification triggers (8 places)
- ❌ Configure production SMTP
- ❌ Test everything

### **Estimated Time:**
- **Minimum viable**: 1 hour
- **Complete system**: 1-2 weeks
- **Production-ready**: 2-3 weeks (with testing)

---

**Ready to start? I can help you implement any phase! 🚀**
