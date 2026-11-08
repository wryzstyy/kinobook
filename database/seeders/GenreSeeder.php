<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GenreSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $names = [
            'Фантастика', // 1
            'Драма',      // 2
            'Комедия',    // 3
            'Боевик',     // 4
            'Триллер',    // 5
            'Приключения',// 6
            'Мультфильм', // 7
            'Ужасы',      // 8
            'Мелодрама',  // 9
        ];

        DB::table('genres')->insert(array_map(fn ($name) => [
            'name'       => $name,
            'created_at' => $now,
            'updated_at' => $now,
        ], $names));
    }
}