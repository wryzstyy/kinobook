<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreHallRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:255'],
            'rows_count'    => ['required', 'integer', 'min:1', 'max:30'],
            'seats_per_row' => ['required', 'integer', 'min:1', 'max:40'],
        ];
    }
}