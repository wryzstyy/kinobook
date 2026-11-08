<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class PayBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'card_number' => ['bail', 'required', 'string', function ($attribute, $value, $fail) {
                $digits = preg_replace('/\s+/', '', $value);

                if (! preg_match('/^\d{16}$/', $digits) || ! self::luhn($digits)) {
                    $fail('Неверный номер карты.');
                }
            }],
            'card_holder' => ['required', 'string', 'min:2', 'max:60', 'regex:/^[\pL\s.\-]+$/u'],
            'expiry'      => ['bail', 'required', 'string', 'regex:/^(0[1-9]|1[0-2])\/\d{2}$/', function ($attribute, $value, $fail) {
                if (Carbon::createFromFormat('!m/y', $value)->endOfMonth()->isPast()) {
                    $fail('Срок действия карты истёк.');
                }
            }],
            'cvv'         => ['required', 'digits:3'],
            'email'       => ['nullable', 'email', 'max:255'], // куда отправить билеты; по умолчанию почта аккаунта
        ];
    }

    public function messages(): array
    {
        return [
            'card_holder.regex' => 'Имя держателя: только буквы, пробелы, точки и дефисы.',
            'expiry.regex'      => 'Срок действия в формате ММ/ГГ, например 12/28.',
            'cvv.digits'        => 'CVV должен состоять из 3 цифр.',
        ];
    }

    // Проверка номера карты по алгоритму Луна
    private static function luhn(string $number): bool
    {
        $sum = 0;
        $double = false;

        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $digit = (int) $number[$i];

            if ($double) {
                $digit *= 2;

                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
            $double = ! $double;
        }

        return $sum % 10 === 0;
    }
}