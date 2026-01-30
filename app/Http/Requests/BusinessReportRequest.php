<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BusinessReportRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'reason' => ['required','string','max:191'],
            'details' => ['nullable','string','max:2000'],
            'evidence' => ['nullable','file','mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ];
    }
}
