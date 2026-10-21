<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            GenreSeeder::class,
            MovieSeeder::class,
            GenreMovieSeeder::class,
            HallSeeder::class,
            SeatSeeder::class,
            ScreeningSeeder::class,
            OrderSeeder::class,
            TicketSeeder::class,
        ]);
    }
}