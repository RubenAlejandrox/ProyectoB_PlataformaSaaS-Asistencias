<?php

/**
 * @descripcion  Seeder de datos iniciales: Roles.
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

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    /**
     * Crea los roles del sistema (Administrator, Teacher, Student).
     *
     * @return void
     */
    public function run(): void
    {
        Role::create(['name' => 'Administrator', 'guard_name' => 'web']);
        Role::create(['name' => 'Teacher',       'guard_name' => 'web']);
        Role::create(['name' => 'Student',       'guard_name' => 'web']);

    }
}
