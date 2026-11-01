<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\MovieResource;
use App\Models\Movie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MovieController extends Controller
{
    private function rules(bool $update): array
    {
        $req = $update ? ['sometimes', 'required'] : ['required'];

        $rules = [
            'title'            => [...$req, 'string', 'max:255'],
            'description'      => [...$req, 'string', 'max:5000'],
            'duration_minutes' => [...$req, 'integer', 'min:1', 'max:600'],
            'age_rating'       => [...$req, 'string', 'max:10'],
            'genre_ids'        => [...$req, 'array', 'min:1'], // у фильма минимум один жанр
            'genre_ids.*'      => ['integer', 'distinct', 'exists:genres,id'],
        ];

        // Постер передаётся только при создании фильма
        if (! $update) {
            $rules['poster'] = ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'];
        }

        return $rules;
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules(false));

        $path = $request->hasFile('poster')
            ? $request->file('poster')->store('posters', 'public')
            : null;

        try {
            $movie = DB::transaction(function () use ($data, $path) {
                $movie = Movie::create(
                    Arr::except($data, ['genre_ids', 'poster']) + ['poster_url' => $path]
                );
                $movie->genres()->sync($data['genre_ids']);

                return $movie;
            });
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('public')->delete($path); // не оставляем файл без фильма
            }

            throw $e;
        }

        return (new MovieResource($movie->load('genres')))->response()->setStatusCode(201);
    }

    public function update(Request $request, Movie $movie)
    {
        $data = $request->validate($this->rules(true));

        DB::transaction(function () use ($movie, $data) {
            $movie->update(Arr::except($data, 'genre_ids'));

            if (isset($data['genre_ids'])) {
                $movie->genres()->sync($data['genre_ids']);
            }
        });

        return new MovieResource($movie->fresh('genres'));
    }

    public function destroy(Movie $movie): JsonResponse
    {
        if ($movie->screenings()->exists()) {
            abort(409, 'У фильма есть сеансы, удалить его нельзя.');
        }

        $movie->deletePosterFile();
        $movie->delete(); // связи с жанрами удалятся каскадом

        return response()->json(['message' => 'Фильм удалён.']);
    }
}