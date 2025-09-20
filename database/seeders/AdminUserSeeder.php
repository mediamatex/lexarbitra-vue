<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if admin user already exists
        $adminEmail = config('app.admin_email', env('ADMIN_EMAIL', 'admin@lexarbitra.com'));

        if (User::where('email', $adminEmail)->exists()) {
            $this->command->info('Admin user already exists: ' . $adminEmail);
            return;
        }

        // Create admin user
        $admin = User::create([
            'name' => env('ADMIN_NAME', 'Admin User'),
            'email' => $adminEmail,
            'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
            'email_verified_at' => now(),
            'is_super_admin' => true,
            'is_active' => true,
            'title' => 'Administrator',
        ]);

        $this->command->info('Admin user created successfully: ' . $admin->email);
    }
}
