# 🚀 Notification System - Setup & Testing Guide

## ✅ **What Was Just Implemented**

### **Files Created:**
```
✅ app/Notifications/ReviewReplyNotification.php
✅ app/Notifications/InquiryResponseNotification.php
✅ app/Notifications/NewLeadNotification.php
✅ app/Notifications/NewReviewNotification.php
✅ app/Observers/ReviewObserver.php
✅ app/Observers/LeadObserver.php
✅ app/Providers/AppServiceProvider.php (updated)
✅ .env.example (updated with SMTP options)
```

### **Features Implemented:**
- ✅ **Auto-notifications** via Model Observers (no manual triggers needed!)
- ✅ **User preference checking** (respects customer settings)
- ✅ **Dual-channel** delivery (Email + In-app notifications)
- ✅ **Queue support** (notifications sent asynchronously)
- ✅ **Error logging** (failures are logged for debugging)

---

## 🎯 **How It Works**

### **Customer Notifications (Automatic):**

1. **Review Reply Notification**
   - **Trigger:** Business owner updates `reply` field in review
   - **Sent to:** Customer who wrote the review
   - **Checks:** `user_preferences.notify_review_reply_received` (email)
   - **Checks:** `user_preferences.notify_review_reply_app` (in-app)

2. **Inquiry Response Notification**
   - **Trigger:** Business owner updates `reply_message` and sets `is_replied = true`
   - **Sent to:** Customer who submitted the lead
   - **Checks:** `user_preferences.notify_inquiry_response_received` (email)
   - **Checks:** `user_preferences.notify_inquiry_response_app` (in-app)

### **Business Owner Notifications (Automatic):**

3. **New Lead Notification**
   - **Trigger:** Customer submits inquiry via `LeadController@store`
   - **Sent to:** Business owner
   - **Checks:** `user_preferences.notify_new_leads` (email)
   - **Always sends:** Database notification (in-app)

4. **New Review Notification**
   - **Trigger:** Customer submits review via `ReviewController@store`
   - **Sent to:** Business owner
   - **Checks:** `user_preferences.notify_new_reviews` (email)
   - **Always sends:** Database notification (in-app)

---

## 📧 **SMTP Setup (Choose One)**

### **OPTION 1: Mailtrap (Recommended for Testing)**

**Best for:** Development, testing, staging

```bash
# 1. Sign up at https://mailtrap.io (FREE)
# 2. Get your credentials from inbox settings
# 3. Update your .env file:

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@yellowbooks.ng"
MAIL_FROM_NAME="YellowBooks Nigeria"
```

**Pros:**
- ✅ Free forever
- ✅ Catches all emails (perfect for testing)
- ✅ Web interface to view emails
- ✅ No risk of sending to real users during testing

---

### **OPTION 2: Mailgun (Recommended for Production)**

**Best for:** Production with high volume

```bash
# 1. Sign up at https://www.mailgun.com
# 2. Add and verify your domain
# 3. Get your API key
# 4. Update your .env file:

MAIL_MAILER=mailgun
MAILGUN_DOMAIN=mg.yellowbooks.ng
MAILGUN_SECRET=key-xxxxxxxxxxxxxxxx
MAILGUN_ENDPOINT=api.mailgun.net
MAIL_FROM_ADDRESS="noreply@yellowbooks.ng"
MAIL_FROM_NAME="YellowBooks Nigeria"
```

**Pricing:**
- Free: 10,000 emails/month (first 3 months)
- Paid: $35/month for 50,000 emails

---

### **OPTION 3: Gmail SMTP (Quick & Easy)**

**Best for:** Small scale, testing with real emails

```bash
# 1. Enable 2-Factor Authentication on your Google Account
# 2. Generate an App Password:
#    - Go to https://myaccount.google.com/security
#    - Click "2-Step Verification"
#    - Scroll to "App passwords"
#    - Generate password for "Mail"
# 3. Update your .env file:

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-16-digit-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="your-email@gmail.com"
MAIL_FROM_NAME="YellowBooks Nigeria"
```

**Limitations:**
- ⚠️ 500 emails per day limit
- ⚠️ Not recommended for production

---

## 🧪 **Testing Instructions**

### **Step 1: Configure SMTP**

```bash
# Copy .env.example to .env if you haven't
cp .env.example .env

# Edit .env and add your SMTP credentials
nano .env

# For testing, use Mailtrap (see Option 1 above)
```

### **Step 2: Run Database Migration**

```bash
# Make sure notification preferences migration is run
php artisan migrate

# Check if notifications table exists
php artisan tinker
>>> \Schema::hasTable('notifications');  # Should return true
>>> \Schema::hasTable('user_preferences');  # Should return true
```

### **Step 3: Start Queue Worker**

```bash
# Notifications are queued for better performance
# Start the queue worker in a separate terminal:

php artisan queue:work

# Keep this running while testing!
```

### **Step 4: Test Each Notification**

#### **Test 1: New Lead Notification (Business Owner)**

```bash
php artisan tinker

# Create a test lead
$business = App\Models\Business::first();
$lead = App\Models\Lead::create([
    'business_id' => $business->id,
    'user_id' => null,  # Guest inquiry
    'client_name' => 'Test Customer',
    'email' => 'test@example.com',
    'phone' => '+234 800 123 4567',
    'lead_button_text' => 'Book Now',
    'status' => 'new',
]);

# Check your email (Mailtrap inbox or Gmail)
# Business owner should receive: "🎉 New Lead: Book Now for [Business Name]"
```

#### **Test 2: Review Reply Notification (Customer)**

```bash
php artisan tinker

# Get a review with a customer
$review = App\Models\Review::whereNotNull('user_id')->first();

# Add a reply (this triggers the notification)
$review->update([
    'reply' => 'Thank you so much for your feedback! We really appreciate it.'
]);

# Check customer's email
# Customer should receive: "[Business Name] replied to your review"
```

#### **Test 3: Inquiry Response Notification (Customer)**

```bash
php artisan tinker

# Get a lead with a customer
$lead = App\Models\Lead::whereNotNull('user_id')->first();

# Add a response (this triggers the notification)
$lead->update([
    'is_replied' => true,
    'reply_message' => 'Thank you for your inquiry! We would love to help you. Please call us at...'
]);

# Check customer's email
# Customer should receive: "[Business Name] responded to your inquiry"
```

#### **Test 4: New Review Notification (Business Owner)**

```bash
php artisan tinker

# Create a test review
$business = App\Models\Business::first();
$customer = App\Models\User::where('role', 'customer')->first();

$review = App\Models\Review::create([
    'reviewable_type' => 'App\Models\Business',
    'reviewable_id' => $business->id,
    'user_id' => $customer->id,
    'rating' => 5,
    'comment' => 'Amazing service! Highly recommended.',
    'is_approved' => true,
    'published_at' => now(),
]);

# Check business owner's email
# Owner should receive: "⭐ New Review: 5 stars for [Business Name]"
```

---

## ✅ **Verification Checklist**

After testing, verify the following:

### **Email Delivery**
- [ ] Emails appear in Mailtrap inbox (or Gmail)
- [ ] Subject lines are correct
- [ ] Business/customer names are populated
- [ ] Links work and go to correct pages
- [ ] Email formatting looks good

### **In-App Notifications**
- [ ] Visit `/customer` dashboard - check notification bell
- [ ] Visit `/business` dashboard - check notification bell
- [ ] Click notifications - they mark as read
- [ ] Links in notifications work

### **Preference Respect**
- [ ] Disable email in customer preferences
- [ ] Submit test inquiry response
- [ ] Verify NO email sent (only in-app notification)
- [ ] Re-enable and test again

### **Queue Processing**
- [ ] Check `queue:work` terminal - see jobs processing
- [ ] Check `jobs` table - should be empty after processing
- [ ] Check `failed_jobs` table - should be empty (no failures)

---

## 🚨 **Troubleshooting**

### **Problem: Emails not sending**

```bash
# 1. Check if queue worker is running
ps aux | grep "queue:work"

# 2. Check mail logs
tail -f storage/logs/laravel.log | grep -i mail

# 3. Test mail connection
php artisan tinker
>>> Mail::raw('Test email', function($msg) { 
      $msg->to('test@example.com')->subject('Test'); 
    });

# 4. Check failed jobs
php artisan queue:failed

# 5. Retry failed jobs
php artisan queue:retry all
```

### **Problem: Notifications not triggered**

```bash
# Check if observers are registered
php artisan tinker
>>> app(\App\Providers\AppServiceProvider::class)->boot();
>>> "Observers registered";

# Check logs
tail -f storage/logs/laravel.log | grep -i notification

# Manually trigger
$review = Review::first();
$review->update(['reply' => 'Test reply ' . time()]);
```

### **Problem: User preferences not being checked**

```bash
# Verify user has preferences
php artisan tinker
>>> $user = User::find(1);
>>> $user->preferences;  # Should not be null
>>> $user->preferences->notify_review_reply_received;  # Should be true/false

# If null, create preferences
>>> App\Models\UserPreference::getForUser($user->id);
```

---

## 📊 **Monitoring in Production**

### **Check Notification Stats**

```bash
php artisan tinker

# Count notifications sent today
>>> App\Models\Notification::whereDate('created_at', today())->count();

# Count by type
>>> DB::table('notifications')
      ->selectRaw('data->>"$.type" as type, count(*) as count')
      ->groupBy('type')
      ->get();

# Find failed jobs
>>> DB::table('failed_jobs')->count();
```

### **Set Up Supervisor (Production)**

Create `/etc/supervisor/conf.d/yellowbooks-worker.conf`:

```ini
[program:yellowbooks-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/yellowbooks/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/yellowbooks/storage/logs/worker.log
stopwaitsecs=3600
```

Then:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start yellowbooks-worker:*
```

---

## 🎨 **Customizing Email Templates**

The notifications use Laravel's default email template. To customize:

### **Option 1: Publish Laravel's Mail Template**

```bash
php artisan vendor:publish --tag=laravel-mail
```

Edit: `resources/views/vendor/mail/html/themes/default.css`

### **Option 2: Create Custom Blade Templates**

1. Create `resources/views/emails/layouts/master.blade.php`
2. Update notification classes to use `->view('emails.custom-template')`

---

## 📈 **Next Steps**

### **Phase 2: System Notifications**
- [ ] Verification approved/rejected notifications
- [ ] Premium expiring notifications (scheduled)
- [ ] Campaign ending notifications

### **Phase 3: Advanced Features**
- [ ] Telegram integration
- [ ] WhatsApp integration (via Twilio/Africa's Talking)
- [ ] SMS notifications for urgent matters
- [ ] Push notifications (web push)

### **Phase 4: Analytics**
- [ ] Track email open rates
- [ ] Track link click-through rates
- [ ] A/B test subject lines
- [ ] Monitor bounce rates

---

## 🎉 **You're All Set!**

Your notification system is now **fully operational**! Here's what happens automatically:

1. ✅ Customer submits inquiry → Business owner gets email + in-app notification
2. ✅ Customer writes review → Business owner gets email + in-app notification
3. ✅ Business owner replies to review → Customer gets email + in-app notification
4. ✅ Business owner responds to inquiry → Customer gets email + in-app notification

All notifications respect user preferences and are sent asynchronously via queues!

---

## 📚 **Quick Reference**

### **Artisan Commands**
```bash
# Start queue worker
php artisan queue:work

# View failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Clear all queued jobs
php artisan queue:flush

# Test email config
php artisan tinker
>>> Mail::raw('Test', fn($msg) => $msg->to('test@example.com')->subject('Test'));
```

### **Important Directories**
- Notifications: `app/Notifications/`
- Observers: `app/Observers/`
- Email views: `resources/views/emails/` (for custom templates)
- Logs: `storage/logs/laravel.log`

---

**Need help? Check the logs or test individual components!** 🚀
