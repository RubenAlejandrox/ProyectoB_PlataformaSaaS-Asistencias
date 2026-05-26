<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Se ejecutan en orden estricto de jerarquía
        $this->call([
            RolesSeeder::class,      // 1. Crea los roles (ej. Administrator)
            PlansSeeder::class,      // 2. Crea los planes de suscripción (Basic, Pro)
            AdminUserSeeder::class,  // 3. Crea la institución y el usuario administrador
        ]);
    }
}