<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreMovieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'            => ['required', 'string', 'max:255'],
            'description'      => ['required', 'string', 'max:5000'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:600'],
            'age_rating'       => ['required', 'string', 'max:10'],
            'genre_ids'        => ['required', 'array', 'min:1'], // у фильма минимум один жанр
            'genre_ids.*'      => ['integer', 'distinct', 'exists:genres,id'],
            'poster'           => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'genre_ids.min' => 'Укажите хотя бы один жанр.',
            'poster.image'  => 'Постер должен быть изображением.',
            'poster.mimes'  => 'Допустимые форматы постера: jpg, png, webp.',
            'poster.max'    => 'Размер постера не больше 2 МБ.',
        ];
    }
}