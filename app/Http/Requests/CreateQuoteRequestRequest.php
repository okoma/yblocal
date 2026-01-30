<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateQuoteRequestRequest extends FormRequest
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
            'category_id' => ['required', 'exists:categories,id'],
            'location_id' => ['required', 'exists:locations,id'],
            'title' => ['required', 'string', 'min:10', 'max:200'],
            'description' => ['required', 'string', 'min:20', 'max:2000'],
            'budget_min' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
            'budget_max' => ['nullable', 'numeric', 'min:0', 'max:100000000', 'gt:budget_min'],
            'expires_at' => ['nullable', 'date', 'after:now', 'before:+30 days'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'urgent' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.min' => 'Title must be at least 10 characters',
            'description.min' => 'Please provide more details (at least 20 characters)',
            'budget_max.gt' => 'Maximum budget must be greater than minimum budget',
            'expires_at.after' => 'Expiry date must be in the future',
            'expires_at.before' => 'Quote request cannot be open for more than 30 days',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default expiry if not provided (7 days from now)
        if (!$this->has('expires_at')) {
            $this->merge([
                'expires_at' => now()->addDays(7),
            ]);
        }

        // Sanitize text inputs
        $this->merge([
            'title' => strip_tags($this->title ?? ''),
            'description' => strip_tags($this->description ?? ''),
        ]);
    }
}
