<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserBasicDataUpdate extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'surname' => 'nullable|string',
            'password' => 'required|string|min:9',
        ];
    }
}
