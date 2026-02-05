<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'seller1@example.com'],
            ['name' => '出品者ユーザーA', 'password' => Hash::make('password')]
        );

        User::updateOrCreate(
            ['email' => 'seller2@example.com'],
            ['name' => '出品者ユーザーB', 'password' => Hash::make('password')]
        );

        User::updateOrCreate(
            ['email' => 'noitem@example.com'],
            ['name' => '未出品ユーザーC', 'password' => Hash::make('password')]
        );
    }
}