<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class MovieDescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'            => ['required', 'string', 'max:255'],
            'genres'           => ['nullable', 'array', 'max:10'],
            'genres.*'         => ['string', 'max:50'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'notes'            => ['nullable', 'string', 'max:2000'],
        ];
    }
}