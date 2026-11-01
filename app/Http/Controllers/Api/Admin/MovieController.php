<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMovieRequest;
use App\Http\Requests\Admin\UpdateMovieRequest;
use App\Http\Resources\MovieResource;
use App\Models\Movie;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MovieController extends Controller
{
    public function store(StoreMovieRequest $request): JsonResponse
    {
        $data = $request->validated();

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

    public function update(UpdateMovieRequest $request, Movie $movie)
    {
        $data = $request->validated();

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