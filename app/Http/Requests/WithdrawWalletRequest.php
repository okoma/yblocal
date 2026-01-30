<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WithdrawWalletRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $wallet = $this->route('wallet');
        
        if (!$wallet) {
            return false;
        }

        return $this->user()->can('withdraw', $wallet);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $wallet = $this->route('wallet');
        $maxAmount = $wallet ? $wallet->balance : 0;

        return [
            'amount' => ['required', 'numeric', 'min:100', "max:{$maxAmount}"],
            'bank_name' => ['required', 'string', 'max:100'],
            'account_number' => ['required', 'string', 'regex:/^[0-9]{10}$/'],
            'account_name' => ['required', 'string', 'max:100'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'amount.min' => 'Minimum withdrawal amount is NGN 100',
            'amount.max' => 'Insufficient balance',
            'account_number.regex' => 'Account number must be 10 digits',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Sanitize inputs
        $this->merge([
            'bank_name' => strip_tags($this->bank_name ?? ''),
            'account_number' => preg_replace('/[^0-9]/', '', $this->account_number ?? ''),
            'account_name' => strip_tags($this->account_name ?? ''),
            'reason' => strip_tags($this->reason ?? ''),
        ]);
    }
}
