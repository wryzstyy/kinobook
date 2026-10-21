<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GenreMovieSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // [genre_id, movie_id]
        $pairs = [
            [1, 1], [2, 1],
            [1, 2], [5, 2],
            [2, 3],
            [4, 4], [3, 4],
            [3, 5],
        ];

        $rows = array_map(fn ($p) => [
            'genre_id'   => $p[0],
            'movie_id'   => $p[1],
            'created_at' => $now,
            'updated_at' => $now,
        ], $pairs);

        DB::table('genre_movie')->insert($rows);
    }
}