<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\EmailSuppression;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmailWebhookController extends Controller
{
    public function bounce(Request $request)
    {
        $payload = $request->all();

        // Attempt to extract a canonical email from common providers
        $email = $request->input('email') ?? data_get($payload, 'recipient') ?? data_get($payload, 'message.to.0.email');
        $reason = $request->input('reason') ?? data_get($payload, 'reason') ?? data_get($payload, 'event');

        if (empty($email)) {
            Log::warning('EmailWebhookController::bounce called with no email', ['payload' => $payload]);
            return response()->json(['ok' => true]);
        }

        EmailSuppression::updateOrCreate(
            ['email' => $email],
            [
                'reason' => $reason,
                'payload' => $payload,
                'source' => 'mailer_webhook',
            ]
        );

        Log::info('EmailWebhookController: Added suppression for email', ['email' => $email, 'reason' => $reason]);

        return response()->json(['ok' => true]);
    }
}
