<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Models\EmailSuppression;
use App\Models\UserPreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UnsubscribeController extends Controller
{
    public function handle(Request $request)
    {
        $e = $request->query('e');
        $topic = $request->query('t');

        if (!$e) {
            return redirect('/')->with('status', 'Invalid unsubscribe link');
        }

        $email = base64_decode($e);

        if (!$email) {
            return redirect('/')->with('status', 'Invalid unsubscribe link');
        }

        if (!$topic) {
            // global suppression
            EmailSuppression::updateOrCreate([
                'email' => $email,
            ], [
                'reason' => 'user_unsubscribe',
                'source' => 'unsubscribe_link',
            ]);

            // Try to unset newsletter/promotion flags for any user with this email
            UserPreference::whereHas('user', function ($q) use ($email) {
                $q->where('email', $email);
            })->update(['notify_newsletter_customer' => false, 'notify_promotions_customer' => false]);

            return view('notifications.unsubscribed', ['email' => $email, 'topic' => 'all']);
        }

        // topic-specific: map known topics to UserPreference fields
        $mapping = [
            'newsletter' => 'notify_newsletter_customer',
            'promotions' => 'notify_promotions_customer',
        ];

        if (isset($mapping[$topic])) {
            // set user preference if user exists
            UserPreference::whereHas('user', function ($q) use ($email) {
                $q->where('email', $email);
            })->update([$mapping[$topic] => false]);

            // also add to suppression table for safety
            EmailSuppression::updateOrCreate([
                'email' => $email,
            ], [
                'reason' => 'user_unsubscribe_' . $topic,
                'source' => 'unsubscribe_link',
            ]);

            return view('notifications.unsubscribed', ['email' => $email, 'topic' => $topic]);
        }

        // unknown topic -> global
        EmailSuppression::updateOrCreate([
            'email' => $email,
        ], [
            'reason' => 'user_unsubscribe_unknown',
            'source' => 'unsubscribe_link',
        ]);

        return view('notifications.unsubscribed', ['email' => $email, 'topic' => 'all']);
    }
}
