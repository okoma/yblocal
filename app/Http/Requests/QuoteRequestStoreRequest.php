<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuoteRequestStoreRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'title' => ['required','string','max:191'],
            'description' => ['required','string','max:4000'],
            'category_id' => ['nullable','integer','exists:categories,id'],
            'budget' => ['nullable','numeric'],
            'attachments.*' => ['nullable','file','mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ];
    }
}
