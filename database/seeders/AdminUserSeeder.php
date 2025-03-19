<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@gflix.com',
            'password' => Hash::make('admin123!@#'),
            'email_verified_at' => now(),
            'status' => 'active',
            'is_admin' => true,
            'referral_code' => Str::random(10),
            'remember_token' => Str::random(10),
        ]);

        // Create a test user
        User::create([
            'name' => 'Test User',
            'email' => 'test@gflix.com',
            'password' => Hash::make('test123!@#'),
            'email_verified_at' => now(),
            'status' => 'active',
            'is_admin' => false,
            'referral_code' => Str::random(10),
            'remember_token' => Str::random(10),
            'trial_ends_at' => now()->addHours(24),
        ]);
    }
}