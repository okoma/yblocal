<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitQuoteResponseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = auth()->user();
        
        // Must be business owner
        if (!$user || !$user->isBusinessOwner()) {
            return false;
        }

        // Check if user has access to the business being used
        $businessId = $this->input('business_id');
        if ($businessId) {
            $business = \App\Models\Business::find($businessId);
            return $business && $business->user_id === $user->id;
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'quote_request_id' => ['required', 'exists:quote_requests,id'],
            'business_id' => ['required', 'exists:businesses,id'],
            'price' => ['required', 'numeric', 'min:1', 'max:100000000'],
            'delivery_time' => ['required', 'string', 'max:100'],
            'message' => ['required', 'string', 'min:20', 'max:1000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'], // 5MB max
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'price.min' => 'Price must be at least NGN 1',
            'message.min' => 'Please provide more details about your quote (at least 20 characters)',
            'attachments.max' => 'You can upload maximum 5 attachments',
            'attachments.*.max' => 'Each attachment must not exceed 5MB',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Sanitize message
        $this->merge([
            'message' => strip_tags($this->message ?? ''),
            'delivery_time' => strip_tags($this->delivery_time ?? ''),
        ]);
    }
}
