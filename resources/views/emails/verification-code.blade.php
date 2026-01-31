@component('mail::message')
# Email Verification

Your verification code is:

@component('mail::panel')
# **{{ $code }}**
@endcomponent

This code will expire in 15 minutes.

If you didn't request this code, you can safely ignore this email.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
