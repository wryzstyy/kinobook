<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScreeningSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('screenings')->insert([
            ['movie_id' => 1, 'hall_id' => 1, 'starts_at' => '2026-11-01 18:00:00', 'price' => 450.00, 'created_at' => $now, 'updated_at' => $now],
            ['movie_id' => 2, 'hall_id' => 1, 'starts_at' => '2026-11-01 21:00:00', 'price' => 450.00, 'created_at' => $now, 'updated_at' => $now],
            ['movie_id' => 3, 'hall_id' => 2, 'starts_at' => '2026-11-02 19:30:00', 'price' => 700.00, 'created_at' => $now, 'updated_at' => $now],
            ['movie_id' => 4, 'hall_id' => 3, 'starts_at' => '2026-11-02 20:00:00', 'price' => 400.00, 'created_at' => $now, 'updated_at' => $now],
            ['movie_id' => 5, 'hall_id' => 3, 'starts_at' => '2026-11-03 12:00:00', 'price' => 300.00, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}