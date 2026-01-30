<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BusinessClaimRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => ['required','string','max:191'],
            'email' => ['required','email','max:191'],
            'phone' => ['nullable','string','max:32'],
            'message' => ['nullable','string','max:2000'],
            'document' => ['nullable','file','mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }
}
