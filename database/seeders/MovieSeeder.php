<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class MovieSeeder extends Seeder
{
    // Копирует картинку database/seeders/posters/{n}.(jpg|jpeg|png|webp) в хранилище
    private function poster(int $n): ?string
    {
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
            $source = database_path("seeders/posters/{$n}.{$ext}");

            if (File::exists($source)) {
                $path = "posters/seed-{$n}.{$ext}";
                Storage::disk('public')->put($path, File::get($source));

                return $path;
            }
        }

        return null;
    }

    public function run(): void
    {
        $now = now();

        DB::table('movies')->insert([
            ['title' => 'Интерстеллар', 'description' => 'Экипаж космонавтов отправляется сквозь червоточину искать новый дом для человечества.', 'duration_minutes' => 169, 'age_rating' => '12+', 'poster_url' => $this->poster(1), 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'Начало', 'description' => 'Вор, проникающий в сны, получает задание внедрить идею в сознание человека.', 'duration_minutes' => 148, 'age_rating' => '12+', 'poster_url' => $this->poster(2), 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'Побег из Шоушенка', 'description' => 'Банкир, осуждённый по ложному обвинению, пытается выжить в тюрьме и бежать.', 'duration_minutes' => 142, 'age_rating' => '16+', 'poster_url' => $this->poster(3), 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'Джентльмены', 'description' => 'Британский наркобарон хочет продать бизнес, и вокруг сделки начинается игра.', 'duration_minutes' => 113, 'age_rating' => '18+', 'poster_url' => $this->poster(4), 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'Один дома', 'description' => 'Мальчик остаётся один дома и защищает его от грабителей.', 'duration_minutes' => 103, 'age_rating' => '6+', 'poster_url' => $this->poster(5), 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}