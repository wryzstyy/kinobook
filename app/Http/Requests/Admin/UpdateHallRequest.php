<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHallRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => ['sometimes', 'required', 'string', 'max:255'],
            'rows_count'    => ['sometimes', 'required', 'integer', 'min:1', 'max:30'],
            'seats_per_row' => ['sometimes', 'required', 'integer', 'min:1', 'max:40'],
        ];
    }
}