<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');
        $now = now();
        $admin = UserRole::Admin->value;
        $viewer = UserRole::Viewer->value;

        DB::table('users')->insert([
            ['name' => 'Администратор',  'email' => 'admin@kinobook.test',  'password' => $password, 'role' => $admin,  'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Роман Рыбин',    'email' => 'roman@kinobook.test',  'password' => $password, 'role' => $viewer, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ратмир Ахметов', 'email' => 'ratmir@kinobook.test', 'password' => $password, 'role' => $viewer, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Анна Иванова',   'email' => 'anna@kinobook.test',   'password' => $password, 'role' => $viewer, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Олег Петров',    'email' => 'oleg@kinobook.test',   'password' => $password, 'role' => $viewer, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}