<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('SEED_ADMIN_EMAIL', 'admin@example.com');
        $password = env('SEED_ADMIN_PASSWORD', 'admin123');

        $admin = Admin::query()->firstOrCreate(
            ['email' => $email],
            ['password' => Hash::make($password, ['rounds' => 12])],
        );

        $this->command->info("Seeded admin user: {$admin->email}");
    }
}
