<?php
namespace App\Http\Controllers;

use App\Http\Requests\BusinessClaimRequest;
use App\Models\Business;
use App\Models\BusinessClaim;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use App\Notifications\BusinessClaimSubmitted;

class BusinessClaimController extends Controller
{
    public function store(BusinessClaimRequest $request, $businessType, $slug)
    {
        $business = Business::where('slug', $slug)->firstOrFail();

        // Optional captcha verification (if enabled)
        if (config('services.recaptcha.enabled')) {
            $token = $request->input('g-recaptcha-response');
            if (empty($token)) {
                return redirect()->back()->withErrors(['captcha' => __('Captcha required')]);
            }
            $resp = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => config('services.recaptcha.secret'),
                'response' => $token,
                'remoteip' => $request->ip(),
            ]);
            $body = $resp->json();
            if (!($body['success'] ?? false)) {
                return redirect()->back()->withErrors(['captcha' => __('Captcha verification failed')]);
            }
        }

        $data = $request->validated();
        $claim = BusinessClaim::create([
            'business_id' => $business->id,
            'user_id' => auth()->id(),
            'claim_message' => $data['message'] ?? null,
            'verification_email' => $data['email'],
            'verification_phone' => $data['phone'] ?? null,
            'status' => 'pending',
        ]);

        Log::info('Business claim submitted', ['claim_id' => $claim->id, 'business_id' => $business->id]);

        // Notify business owner and admin
        try {
            if ($business->owner) {
                $business->owner->notify(new BusinessClaimSubmitted($claim));
            }
            $admin = config('app.admin_email') ?: config('mail.from.address');
            if ($admin) {
                Notification::route('mail', $admin)->notify(new BusinessClaimSubmitted($claim));
            }
        } catch (\Exception $e) {
            Log::error('Error sending claim notifications: ' . $e->getMessage());
        }

        return redirect()->back()->with('status', __('Your claim has been submitted.'));
    }
}
