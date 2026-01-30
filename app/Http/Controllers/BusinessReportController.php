<?php
namespace App\Http\Controllers;

use App\Http\Requests\BusinessReportRequest;
use App\Models\Business;
use App\Models\BusinessReport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use App\Notifications\BusinessReported;

class BusinessReportController extends Controller
{
    public function store(BusinessReportRequest $request, $businessType, $slug)
    {
        $business = Business::where('slug', $slug)->firstOrFail();

        // Optional captcha verification
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
        $report = BusinessReport::create([
            'business_id' => $business->id,
            'reported_by' => auth()->id(),
            'reason' => $data['reason'],
            'description' => $data['details'] ?? null,
        ]);

        Log::info('Business reported', ['report_id' => $report->id, 'business_id' => $business->id]);

        try {
            if ($business->owner) {
                $business->owner->notify(new BusinessReported($report));
            }
            $admin = config('app.admin_email') ?: config('mail.from.address');
            if ($admin) {
                Notification::route('mail', $admin)->notify(new BusinessReported($report));
            }
        } catch (\Exception $e) {
            Log::error('Error sending report notifications: ' . $e->getMessage());
        }

        return redirect()->back()->with('status', __('Thank you. Your report has been received.'));
    }
}
