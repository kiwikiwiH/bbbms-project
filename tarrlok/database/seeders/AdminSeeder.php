<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['role' => 'admin', 'hospital_id' => null],
            [
                'name' => env('TARRLOK_ADMIN_NAME', 'Tarrlok Platform Admin'),
                'email' => env('TARRLOK_ADMIN_EMAIL', 'admin@tarrlok.gh'),
                'password' => env('TARRLOK_ADMIN_PASSWORD', 'TarrlokAdmin2024!'),
                'status' => 'active',
                'job_title' => 'Platform Administrator',
                'email_verified_at' => now(),
            ]
        );
    }
}
