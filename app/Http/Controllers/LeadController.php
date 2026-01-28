<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Lead;
use App\Http\Requests\StoreLeadRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LeadController extends Controller
{
    /**
     * Submit a lead/inquiry for a business
     * 
     * @param StoreLeadRequest $request
     * @param string $businessType Business type slug
     * @param string $slug Business slug
     * @return JsonResponse
     */
    public function store(StoreLeadRequest $request, string $businessType, string $slug): JsonResponse
    {
        $business = Business::where('slug', $slug)
            ->where('status', 'active')
            ->whereHas('businessType', function ($query) use ($businessType) {
                $query->where('slug', $businessType);
            })
            ->firstOrFail();

        // Validation is handled by StoreLeadRequest

        // Handle file uploads if any (for custom fields)
        $customFields = $request->custom_fields ?? [];
        if ($request->hasFile('custom_fields')) {
            foreach ($request->file('custom_fields') as $key => $file) {
                if (is_file($file)) {
                    $path = $file->store('lead-attachments', 'public');
                    $customFields[$key] = $path;
                }
            }
        }

        try {
            // Create lead
            $lead = Lead::create([
                'business_id' => $business->id,
                'user_id' => Auth::id(), // null if guest
                'client_name' => $request->client_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'whatsapp' => $request->whatsapp,
                'lead_button_text' => $request->lead_button_text ?? 'Contact Business',
                'custom_fields' => !empty($customFields) ? $customFields : null,
                'status' => 'new',
                'is_replied' => false,
            ]);

            // Update business aggregate stats
            $business->updateAggregateStats();

            // TODO: Send notification email to business owner
            // TODO: Send confirmation email to lead submitter

            return response()->json([
                'success' => true,
                'message' => 'Your inquiry has been sent successfully! The business will contact you soon.',
                'lead_id' => $lead->id,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Lead submission failed', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit your inquiry. Please try again later.',
            ], 500);
        }
    }
}
