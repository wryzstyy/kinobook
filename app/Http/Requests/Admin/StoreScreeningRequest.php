<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreScreeningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'movie_id'  => ['required', 'integer', 'exists:movies,id'],
            'hall_id'   => ['required', 'integer', 'exists:halls,id'],
            'starts_at' => ['required', 'date', 'after:now'],
            'price'     => ['required', 'numeric', 'gt:0', 'max:99999'],
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