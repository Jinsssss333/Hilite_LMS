<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'   => 'required|string|max:255',
            'phone'  => 'required|string|max:20',
            'email'  => 'nullable|email|max:255',
            'source' => 'nullable|in:manual,csv,webhook',
            'notes'  => 'nullable|string|max:2000',
        ];
    }
}
