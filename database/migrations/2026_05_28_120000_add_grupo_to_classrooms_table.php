<?php

/**
 * @descripcion  Migración de esquema: add_grupo_to_classrooms_table.
 *
 * @autor          Rubén Alejandro Nolasco Ruiz
 * @autorizador    Rubén Alejandro Nolasco Ruiz
 * @prueba         Diego Miguel Hernandez Fabela
 * @mantenimiento  Ghael Garcia Manjarrez
 *
 * @version      1.0.0
 * @creado       2026-05-28
 * @modificado   2026-06-02
 *
 * @cambios       *               2026-06-02 - Incorporación de cabecera de prólogo conforme estándar
 */


declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Solo para bases existentes sin columna grupo.
     * Instalaciones nuevas ya la incluyen en create_classrooms_table.
     */
    public function up(): void
    {
        if (Schema::hasColumn('classrooms', 'grupo')) {
            return;
        }

        Schema::table('classrooms', function (Blueprint $table) {
            $table->char('grupo', 6)->nullable()->after('period');
        });

        $used = [];
        DB::table('classrooms')->orderBy('created_at')->orderBy('id')->each(function ($row) use (&$used) {
            do {
                $grupo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            } while (isset($used[$grupo]));

            $used[$grupo] = true;

            DB::table('classrooms')->where('id', $row->id)->update(['grupo' => $grupo]);
        });

        DB::statement('ALTER TABLE classrooms ALTER COLUMN grupo SET NOT NULL');

        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropUnique(['teacher_id', 'subject_name', 'period']);
            $table->unique(['teacher_id', 'subject_name', 'period', 'grupo']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('classrooms', 'grupo')) {
            return;
        }

        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropUnique(['teacher_id', 'subject_name', 'period', 'grupo']);
            $table->unique(['teacher_id', 'subject_name', 'period']);
            $table->dropColumn('grupo');
        });
    }
};
