<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBusinessRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $business = $this->route('business');
        
        if (!$business) {
            return false;
        }

        return $this->user()->can('update', $business);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $businessId = $this->route('business')?->id;

        return [
            'business_name' => ['required', 'string', 'min:2', 'max:255'],
            'business_type_id' => ['required', 'exists:business_types,id'],
            'description' => ['nullable', 'string', 'max:5000'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'website' => ['nullable', 'url', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'state_location_id' => ['required', 'exists:locations,id'],
            'city_location_id' => ['required', 'exists:locations,id'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:2048'],
            'cover_photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:5120'],
            'gallery' => ['nullable', 'array', 'max:10'],
            'gallery.*' => ['image', 'mimes:jpeg,jpg,png', 'max:3072'],
            'business_hours' => ['nullable', 'json'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['exists:categories,id'],
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['exists:amenities,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'business_name.min' => 'Business name must be at least 2 characters',
            'phone.required' => 'Phone number is required',
            'address.required' => 'Business address is required',
            'logo.max' => 'Logo must not exceed 2MB',
            'cover_photo.max' => 'Cover photo must not exceed 5MB',
            'gallery.max' => 'You can upload maximum 10 gallery images',
            'gallery.*.max' => 'Each gallery image must not exceed 3MB',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Sanitize text inputs
        $this->merge([
            'business_name' => strip_tags($this->business_name ?? ''),
            'description' => $this->description ? strip_tags($this->description, '<p><br><ul><ol><li><strong><em>') : null,
            'address' => strip_tags($this->address ?? ''),
        ]);
    }
}
