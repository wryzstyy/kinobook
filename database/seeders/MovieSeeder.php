<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MovieSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('movies')->insert([
            ['title' => 'Интерстеллар', 'description' => 'Экипаж космонавтов отправляется сквозь червоточину искать новый дом для человечества.', 'duration_minutes' => 169, 'age_rating' => '12+', 'poster_url' => null, 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'Начало', 'description' => 'Вор, проникающий в сны, получает задание внедрить идею в сознание человека.', 'duration_minutes' => 148, 'age_rating' => '12+', 'poster_url' => null, 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'Побег из Шоушенка', 'description' => 'Банкир, осуждённый по ложному обвинению, пытается выжить в тюрьме и бежать.', 'duration_minutes' => 142, 'age_rating' => '16+', 'poster_url' => null, 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'Джентльмены', 'description' => 'Британский наркобарон хочет продать бизнес, и вокруг сделки начинается игра.', 'duration_minutes' => 113, 'age_rating' => '18+', 'poster_url' => null, 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'Один дома', 'description' => 'Мальчик остаётся один дома и защищает его от грабителей.', 'duration_minutes' => 103, 'age_rating' => '6+', 'poster_url' => null, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}