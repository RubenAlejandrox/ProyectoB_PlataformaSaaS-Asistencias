<?php

/**
 * @descripcion  Migración de esquema: ensure_public_users_id_uuid_before_classrooms.
 *
 * @autor          Rubén Alejandro Nolasco Ruiz
 * @autorizador    Rubén Alejandro Nolasco Ruiz
 * @prueba         Diego Miguel Hernandez Fabela
 * @mantenimiento  Ghael Garcia Manjarrez
 *
 * @version      1.0.0
 * @creado       2026-05-14
 * @modificado   2026-06-02
 *
 * @cambios       *               2026-06-02 - Incorporación de cabecera de prólogo conforme estándar
 */


declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Supabase may leave a legacy public.users (int8 id). If it survives rename logic or pooler
 * ordering hides the new table, FKs from uuid columns will fail. Recreate public.users here
 * when id is not uuid — keep schema aligned with 2026_05_14_035322_create_users_table.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        if (! Schema::hasTable('users')) {
            return;
        }

        $row = DB::selectOne(
            "select c.relkind as relkind, t.typname as id_typname
             from pg_catalog.pg_attribute a
             inner join pg_catalog.pg_class c on c.oid = a.attrelid
             inner join pg_catalog.pg_namespace n on n.oid = c.relnamespace
             inner join pg_catalog.pg_type t on t.oid = a.atttypid
             where n.nspname = 'public'
               and c.relname = 'users'
               and a.attname = 'id'
               and a.attnum > 0
               and not a.attisdropped"
        );

        if (! $row || $row->id_typname === 'uuid') {
            return;
        }

        if (! in_array($row->relkind, ['r', 'p'], true)) {
            throw new RuntimeException(
                'public.users exists but is not a plain table (relkind '.$row->relkind.'). '.
                'Drop or rename it in Supabase, then run migrations again.'
            );
        }

        DB::statement('DROP TABLE IF EXISTS public.users CASCADE');

        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('institution_id')->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('password_hash');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        //
    }
};
