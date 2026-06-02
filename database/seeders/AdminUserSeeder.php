<?php

/**
 * @descripcion  Seeder de datos iniciales: AdminUser.
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
use App\Models\Institution;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    /**
     * Crea la institución demo y el usuario administrador inicial.
     *
     * @return void
     */
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