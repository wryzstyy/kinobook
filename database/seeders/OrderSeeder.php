<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // user_id: 2, 2, 3, 4, 5
        DB::table('orders')->insert([
            ['user_id' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 5, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}