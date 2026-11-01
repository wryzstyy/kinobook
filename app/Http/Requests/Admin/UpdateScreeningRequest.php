<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateScreeningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'movie_id'  => ['sometimes', 'required', 'integer', 'exists:movies,id'],
            'hall_id'   => ['sometimes', 'required', 'integer', 'exists:halls,id'],
            'starts_at' => ['sometimes', 'required', 'date', 'after:now'],
            'price'     => ['sometimes', 'required', 'numeric', 'gt:0', 'max:99999'],
        ];
    }

    public function messages(): array
    {
        return [
            'starts_at.after' => 'Сеанс должен начинаться в будущем.',
            'price.gt'        => 'Цена должна быть больше 0.',
        ];
    }
}