<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMovieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Постер при редактировании не принимается
        return [
            'title'            => ['sometimes', 'required', 'string', 'max:255'],
            'description'      => ['sometimes', 'required', 'string', 'max:5000'],
            'duration_minutes' => ['sometimes', 'required', 'integer', 'min:1', 'max:600'],
            'age_rating'       => ['sometimes', 'required', 'string', 'max:10'],
            'genre_ids'        => ['sometimes', 'required', 'array', 'min:1'],
            'genre_ids.*'      => ['integer', 'distinct', 'exists:genres,id'],
        ];
    }
}