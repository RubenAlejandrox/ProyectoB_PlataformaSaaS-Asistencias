<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $institution = Institution::create([
            'name'      => 'GAMA Demo',
            'is_active' => true,
        ]);

        $admin = User::create([
            'institution_id'        => $institution->id,
            'first_name'            => 'Admin',
            'last_name'             => 'GAMA',
            'email'                 => 'admin@gama.com',
            'password_hash'         => bcrypt('Admin1234$'),
            'is_active'             => true,
            'failed_login_attempts' => 0,
        ]);

        $admin->assignRole('Administrator');
    }
}
