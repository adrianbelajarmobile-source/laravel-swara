<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Role;
use Database\Seeders\RewardSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed roles terlebih dahulu
        $this->call(RoleSeeder::class);

        // Ambil role user
        $roleUser = Role::where('name', 'user')->first();

        if (!$roleUser) {
            throw new \Exception('Role user tidak ditemukan');
        }

        // Buat user
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'password' => Hash::make('password'),
                'role_id' => $roleUser->id,
                'total_points' => 0,
            ]
        );

        // Buat profile (karena name ada di user_profiles)
        UserProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'name' => 'Test User',
            ]
        );

        $this->call(RewardSeeder::class);
    }
}
