<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run()
    {
        Role::firstOrCreate(
            ['name' => 'user']
        );

        Role::firstOrCreate(
            ['name' => 'admin']
        );

        Role::firstOrCreate(
            ['name' => 'influencer']
        );
    }
}
