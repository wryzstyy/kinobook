<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MovieListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'genre_id' => ['nullable', 'integer', 'exists:genres,id'],
            'search'   => ['nullable', 'string', 'max:100'],
        ];
    }
}