<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TicketSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // [order_id, screening_id, hall_id, row_number, [номера мест]]
        $plan = [
            [1, 1, 1, 1, [1, 2]],
            [2, 3, 2, 1, [2]],
            [3, 1, 1, 2, [3]],
            [4, 4, 3, 1, [1, 2, 3]],
            [5, 5, 3, 2, [4]],
        ];

        $rows = [];

        foreach ($plan as [$orderId, $screeningId, $hallId, $row, $seatNumbers]) {
            foreach ($seatNumbers as $seatNumber) {
                $seatId = DB::table('seats')
                    ->where('hall_id', $hallId)
                    ->where('row_number', $row)
                    ->where('seat_number', $seatNumber)
                    ->value('id');

                $rows[] = [
                    'order_id'     => $orderId,
                    'screening_id' => $screeningId,
                    'seat_id'      => $seatId,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ];
            }
        }

        DB::table('tickets')->insert($rows);
    }
}