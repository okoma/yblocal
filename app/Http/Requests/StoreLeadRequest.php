<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Business;

class StoreLeadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Allow both authenticated and guest users to submit leads
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Get business from route parameter
        $business = Business::where('slug', $this->route('slug'))->first();
        
        // Base rules
        $rules = [
            'client_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'lead_button_text' => 'nullable|string|max:100',
            'custom_fields' => 'nullable|array',
        ];

        // Add custom field validations if business type has them
        if ($business && $business->businessType) {
            $leadFormFields = $business->businessType->lead_form_fields ?? [];
            
            if (!empty($leadFormFields)) {
                foreach ($leadFormFields as $field) {
                    $fieldName = $field['name'] ?? null;
                    $fieldRequired = $field['required'] ?? false;
                    $fieldType = $field['type'] ?? 'text';
                    
                    if ($fieldName) {
                        $rule = $fieldRequired ? 'required' : 'nullable';
                        
                        switch ($fieldType) {
                            case 'email':
                                $rule .= '|email';
                                break;
                            case 'number':
                                $rule .= '|numeric';
                                break;
                            case 'date':
                                $rule .= '|date';
                                break;
                            case 'file':
                                $rule .= '|file|max:10240'; // 10MB max
                                break;
                        }
                        
                        $rules["custom_fields.{$fieldName}"] = $rule;
                    }
                }
            }
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'client_name.required' => 'Please enter your name.',
            'client_name.max' => 'Name cannot exceed 255 characters.',
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Please enter a valid email address.',
            'email.max' => 'Email cannot exceed 255 characters.',
            'phone.max' => 'Phone number cannot exceed 20 characters.',
            'whatsapp.max' => 'WhatsApp number cannot exceed 20 characters.',
            'lead_button_text.max' => 'Inquiry type cannot exceed 100 characters.',
        ];
    }
}
