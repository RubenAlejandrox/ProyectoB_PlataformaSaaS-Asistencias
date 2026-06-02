<?php

/**
 * @descripcion  Seeder de datos iniciales: Database.
 *
 * @autor          Rubén Alejandro Nolasco Ruiz
 * @autorizador    Rubén Alejandro Nolasco Ruiz
 * @prueba         Diego Miguel Hernandez Fabela
 * @mantenimiento  Ghael Garcia Manjarrez
 *
 * @version      1.0.0
 * @creado       2026-06-02
 * @modificado   2026-06-02
 *
 * @cambios       *               2026-06-02 - Incorporación de cabecera de prólogo conforme estándar
 */


declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Ejecuta los seeders base en orden de dependencias.
     *
     * @return void
     */
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