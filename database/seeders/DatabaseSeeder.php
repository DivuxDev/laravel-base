<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Admin user
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'     => 'Admin',
                'password' => bcrypt('password'),
                'role'     => 'admin',
            ]
        );

        // Normal user
        User::updateOrCreate(
            ['email' => 'user@example.com'],
            [
                'name'     => 'User',
                'password' => bcrypt('password'),
                'role'     => 'user',
            ]
        );

        // Legacy test user (kept for compatibility)
        User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name'     => 'Test User',
                'password' => bcrypt('password'),
                'role'     => 'user',
            ]
        );
    }
}
