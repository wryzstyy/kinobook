<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // user_id: 2, 2, 3, 4, 5. Все заказы оплачены
        $rows = array_map(fn ($userId) => [
            'user_id'    => $userId,
            'status'     => 'paid',
            'paid_at'    => $now,
            'expires_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ], [2, 2, 3, 4, 5]);

        DB::table('orders')->insert($rows);
    }
}