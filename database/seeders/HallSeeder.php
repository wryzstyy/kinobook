<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HallSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('halls')->insert([
            ['name' => 'Зал 1',       'rows_count' => 12, 'seats_per_row' => 15, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Зал 2 (VIP)', 'rows_count' => 6, 'seats_per_row' => 8, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Зал 3',       'rows_count' => 12, 'seats_per_row' => 15, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}