<?php

/**
 * @descripcion  Asegura la tabla Laravel `sessions` (driver database) con user_id UUID.
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
 * @cambios       *               2026-06-02 - Tabla HTTP sessions alineada a users.id UUID
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sessions')) {
            $this->alignExistingSessionsUserIdColumn();

            return;
        }

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->uuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        // No eliminar: puede contener sesiones activas en producción.
    }

    private function alignExistingSessionsUserIdColumn(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        $row = DB::selectOne(
            "select t.typname as id_typname
             from pg_catalog.pg_attribute a
             inner join pg_catalog.pg_class c on c.oid = a.attrelid
             inner join pg_catalog.pg_namespace n on n.oid = c.relnamespace
             inner join pg_catalog.pg_type t on t.oid = a.atttypid
             where n.nspname = 'public'
               and c.relname = 'sessions'
               and a.attname = 'user_id'
               and a.attnum > 0
               and not a.attisdropped"
        );

        if (! $row || $row->id_typname === 'uuid') {
            return;
        }

        DB::statement('ALTER TABLE sessions DROP CONSTRAINT IF EXISTS sessions_user_id_foreign');
        DB::statement('ALTER TABLE sessions ALTER COLUMN user_id TYPE uuid USING NULL');
        DB::statement(
            'ALTER TABLE sessions ADD CONSTRAINT sessions_user_id_foreign
             FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE'
        );
    }
};
