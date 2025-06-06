<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecurrencyEndRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'recurrency_end' => 'present|nullable|date_format:Y-m-d',
        ];
    }
}
