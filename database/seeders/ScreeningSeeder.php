<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScreeningSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $slots = ['11:00', '14:30', '18:00', '21:30'];
        $days = 4; // сеансы на ближайшие 4 дня, начиная с завтра
        $basePrice = [1 => 450, 2 => 700, 3 => 400]; // по залам; утренние сеансы дешевле на 100

        // Первые 5 сеансов заданы вручную: на них оформлены билеты из TicketSeeder
        // [movie_id, hall_id, день (1 = завтра), время]
        $fixed = [
            [1, 1, 1, '18:00'],
            [2, 1, 1, '21:30'],
            [3, 2, 2, '18:00'],
            [4, 3, 2, '18:00'],
            [5, 3, 3, '11:00'],
        ];

        $plan = [];
        $taken = [];

        foreach ($fixed as [$movie, $hall, $day, $time]) {
            $plan[] = [$movie, $hall, $day, $time];
            $taken["{$day}-{$hall}-{$time}"] = true;
        }

        // Остальные слоты: фильмы по кругу, сначала новые (6-20), потом первые пять
        $queue = array_merge(range(6, 20), range(1, 5));
        $i = 0;

        for ($day = 1; $day <= $days; $day++) {
            foreach ([1, 2, 3] as $hall) {
                foreach ($slots as $time) {
                    if (isset($taken["{$day}-{$hall}-{$time}"])) {
                        continue;
                    }

                    $plan[] = [$queue[$i % count($queue)], $hall, $day, $time];
                    $i++;
                }
            }
        }

        $rows = array_map(function ($p) use ($basePrice, $now) {
            [$movie, $hall, $day, $time] = $p;

            return [
                'movie_id'   => $movie,
                'hall_id'    => $hall,
                'starts_at'  => now()->startOfDay()->addDays($day)->setTimeFromTimeString($time)->toDateTimeString(),
                'price'      => $basePrice[$hall] - ($time === '11:00' ? 100 : 0),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $plan);

        DB::table('screenings')->insert($rows);
    }
}