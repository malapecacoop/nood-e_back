<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MassInviteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // TODO: validate emails in the array
        return [
            'emails' => 'required|array',
        ];
    }
}
