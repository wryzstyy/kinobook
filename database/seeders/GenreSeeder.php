<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GenreSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('genres')->insert([
            ['name' => 'Фантастика', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Драма',      'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Комедия',    'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Боевик',     'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Триллер',    'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}