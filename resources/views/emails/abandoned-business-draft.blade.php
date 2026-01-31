@component('mail::message')
# {{ $reminderNumber === 1 ? 'Complete Your Business Listing' : "Don't Lose Your Progress!" }}

Hi there! 👋

We noticed you started creating a business listing for **{{ $businessName ?? 'your business' }}** but didn't finish.

## Your Progress
You're {{ $completionPercentage }}% done (Step {{ $currentStep }} of 4)!

@if($reminderNumber === 1)
Don't lose all the work you've already put in. It only takes a few more minutes to complete your listing and start attracting customers.
@else
This is a friendly reminder that your draft is still waiting. Complete your listing now before it expires!
@endif

## What You'll Get
✅ Free business listing on our platform  
✅ Reach thousands of potential customers  
✅ Manage reviews and responses  
✅ Track leads and customer interactions  
✅ Premium upgrade options available  

@component('mail::button', ['url' => $resumeUrl])
Continue Where You Left Off
@endcomponent

Your progress has been saved, so you can pick up right where you left off.

## Need Help?
If you're having trouble completing your listing or have any questions, just reply to this email and our team will be happy to assist you.

@if($reminderNumber === 2)
**Note:** This is our final reminder. Your draft will expire in 7 days if not completed.
@endif

Thanks,  
{{ config('app.name') }} Team

---

<small>Not interested? You can ignore this email. Your draft will remain saved for 30 days.</small>
@endcomponent
