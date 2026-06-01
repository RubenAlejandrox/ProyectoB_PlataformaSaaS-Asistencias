<?php

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
