<?php

/**
 * @descripcion  Migración de esquema: rename_legacy_public_users_if_bigint.
 *
 * @autor          Rubén Alejandro Nolasco Ruiz
 * @autorizador    Rubén Alejandro Nolasco Ruiz
 * @prueba         Diego Miguel Hernandez Fabela
 * @mantenimiento  Ghael Garcia Manjarrez
 *
 * @version      1.0.0
 * @creado       2026-05-13
 * @modificado   2026-06-02
 *
 * @cambios       *               2026-06-02 - Incorporación de cabecera de prólogo conforme estándar
 */


declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Supabase or older installs may already define public.users with a non-uuid id (bigint, integer, …).
 * Later migrations expect public.users.id to be uuid (see Spatie pivots + 2026_05_14_035322).
 * This runs before 2026_05_14_035322_create_users_table and only renames a real table whose id is not uuid.
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
            "select c.relkind as relkind,
                    t.typname as id_typname
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

        // Ordinary or partitioned table; skip views / foreign tables.
        if (! $row || ! in_array($row->relkind, ['r', 'p'], true)) {
            return;
        }

        if ($row->id_typname === 'uuid') {
            return;
        }

        Schema::rename('users', 'users_legacy_bigint_renamed_by_laravel_migration');
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        if (Schema::hasTable('users_legacy_bigint_renamed_by_laravel_migration') && ! Schema::hasTable('users')) {
            Schema::rename('users_legacy_bigint_renamed_by_laravel_migration', 'users');
        }
    }
};
