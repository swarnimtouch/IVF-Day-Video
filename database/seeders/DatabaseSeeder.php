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
        // User::factory(10)->create();

        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@meyer.com')],
            [
                'name' => 'Administrator',
                'password' => env('ADMIN_PASSWORD', 'ChangeMe@123'),
                'is_admin' => true,
                'video_status' => 'admin',
            ],
        );
    }
}
