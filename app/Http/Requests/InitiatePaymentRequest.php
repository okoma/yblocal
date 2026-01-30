<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitiatePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && !auth()->user()->is_banned;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:100', 'max:10000000'],
            'payment_gateway_id' => ['required', 'exists:payment_gateways,id'],
            'payable_type' => ['required', 'in:subscription,ad_campaign,wallet'],
            'payable_id' => ['required', 'integer'],
            'redirect_url' => ['nullable', 'url'],
            'metadata' => ['nullable', 'array'],
            'metadata.business_id' => ['nullable', 'exists:businesses,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'amount.min' => 'Minimum payment amount is NGN 100',
            'amount.max' => 'Maximum payment amount is NGN 10,000,000',
            'payment_gateway_id.exists' => 'Invalid payment gateway selected',
            'payable_type.in' => 'Invalid payment type',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Sanitize amount
        if ($this->has('amount')) {
            $this->merge([
                'amount' => (float) $this->amount,
            ]);
        }
    }
}
