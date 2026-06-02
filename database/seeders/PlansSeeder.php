<?php

/**
 * @descripcion  Seeder de datos iniciales: Plans.
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
use Illuminate\Support\Str;        
use App\Models\Plan;              

class PlansSeeder extends Seeder
{
    /**
     * Inserta los planes de suscripción Basic y Pro.
     *
     * @return void
     */
    public function run(): void
    {
        Plan::insert([
            [
                'id'               => Str::uuid(),
                'name'             => 'Basic',
                'price'            => 0.00,
                'max_students'     => 15,
                'max_classrooms'   => 3,
                'duration_months'  => 1,
                'is_active'        => true,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'id'               => Str::uuid(),
                'name'             => 'Pro',
                'price'            => 199.00,
                'max_students'     => 50,
                'max_classrooms'   => 10,
                'duration_months'  => 1,
                'is_active'        => true,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
        ]);
    }
}
