<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScreeningListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date'     => ['nullable', 'date_format:Y-m-d'],
            'movie_id' => ['nullable', 'integer', 'exists:movies,id'],
            'hall_id'  => ['nullable', 'integer', 'exists:halls,id'],
        ];
    }
}