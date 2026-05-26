<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Institution;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $institution = Institution::withoutGlobalScopes()->firstOrCreate(
            ['name' => 'GAMA Demo'],
            ['is_active' => true]
        );

        $admin = User::withoutGlobalScopes()->firstOrCreate(
            ['email' => 'admin@gama.com'],
            [
                'institution_id'        => $institution->id,
                'first_name'            => 'Admin',
                'last_name'             => 'GAMA',
                'password_hash'         => bcrypt('Admin1234$'),
                'is_active'             => true,
                'failed_login_attempts' => 0,
            ]
        );

        if (!$admin->hasRole('Administrator')) {
            $admin->assignRole('Administrator');
        }
    }
}