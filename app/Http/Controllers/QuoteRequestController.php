<?php
namespace App\Http\Controllers;

use App\Http\Requests\QuoteRequestStoreRequest;
use App\Models\QuoteRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\QuoteRequestCreated;

class QuoteRequestController extends Controller
{
    public function store(QuoteRequestStoreRequest $request)
    {
        $data = $request->validated();
        $user = Auth::user();

        $qr = QuoteRequest::create([
            'user_id' => $user->id,
            'title' => $data['title'],
            'description' => $data['description'],
            'category_id' => $data['category_id'] ?? null,
            'budget' => $data['budget'] ?? null,
        ]);

        // Attach files if any
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $qr->addMedia($file)->toMediaCollection('attachments');
            }
        }

        Log::info('Quote request created', ['quote_request_id' => $qr->id, 'user_id' => $user->id]);

        // Notify admin / marketplace team
        try {
            $admin = config('app.admin_email') ?: config('mail.from.address');
            if ($admin) {
                Notification::route('mail', $admin)->notify(new QuoteRequestCreated($qr));
            }
        } catch (\Exception $e) {
            Log::error('Error sending quote request notifications: ' . $e->getMessage());
        }

        return redirect()->route('filament.customer.pages.request-a-quote')
            ->with('status', __('Your quote request has been submitted.'));
    }
}
