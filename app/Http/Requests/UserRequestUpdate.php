<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequestUpdate extends FormRequest
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
            'email' => 'required|email|max:255|unique:users,email,' . $this->user->id,
            'organization_id' => 'nullable|integer|exists:organizations,id',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'role_id' => 'sometimes|integer|exists:roles,id',
        ];
    }
}
