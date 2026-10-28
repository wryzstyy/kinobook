<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MovieResource;
use App\Models\Movie;
use Illuminate\Http\Request;

class MovieController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'genre_id' => ['nullable', 'integer', 'exists:genres,id'],
            'search'   => ['nullable', 'string', 'max:100'],
        ]);

        $movies = Movie::with('genres')
            ->when($data['genre_id'] ?? null, fn ($q, $genreId) => $q->whereHas(
                'genres',
                fn ($g) => $g->where('genres.id', $genreId)
            ))
            ->when($data['search'] ?? null, fn ($q, $search) => $q->where('title', 'like', "%{$search}%"))
            ->orderBy('title')
            ->paginate(10);

        return MovieResource::collection($movies);
    }

    public function show(Movie $movie)
    {
        $movie->load([
            'genres',
            'screenings' => fn ($q) => $q
                ->where('starts_at', '>', now())
                ->orderBy('starts_at')
                ->with('hall')
                ->withCount('tickets'),
        ]);

        return new MovieResource($movie);
    }
}