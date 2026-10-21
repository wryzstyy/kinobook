<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeatSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $rows = [];

        foreach (DB::table('halls')->get() as $hall) {
            for ($r = 1; $r <= $hall->rows_count; $r++) {
                for ($s = 1; $s <= $hall->seats_per_row; $s++) {
                    $rows[] = [
                        'hall_id'     => $hall->id,
                        'row_number'  => $r,
                        'seat_number' => $s,
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ];
                }
            }
        }

        DB::table('seats')->insert($rows);
    }
}