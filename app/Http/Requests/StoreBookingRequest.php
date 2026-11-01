<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'session_id' => ['required', 'integer', 'exists:screenings,id'],
            'seat_ids'   => ['required', 'array', 'min:1', 'max:6'], // в заказе от 1 до 6 билетов
            'seat_ids.*' => ['integer', 'distinct'],
        ];
    }

    public function messages(): array
    {
        return [
            'seat_ids.min' => 'Выберите хотя бы одно место.',
            'seat_ids.max' => 'В одном заказе не больше 6 билетов.',
        ];
    }
}