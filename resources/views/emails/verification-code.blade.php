@component('mail::message')
# Email Verification
<p>Hello,</p>

<p>Your verification code is: <strong>{{ $code }}</strong></p>

<p>This code will expire in 15 minutes.</p>

<p>If you didn't request this code, you can safely ignore this email.</p>

<p>Thanks,<br>{{ config('app.name') }}</p>
If you didn't request this code, you can safely ignore this email.
