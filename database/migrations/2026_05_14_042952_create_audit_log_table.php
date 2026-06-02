<?php

/**
 * @descripcion  Migración de esquema: create_audit_log_table.
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
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audit_log', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('user_id')->constrained('users');
            $table->string('entity');                      // table name
            $table->uuid('entity_id');                     // modified record ID
            $table->string('action');                      // correction/deactivation/closure
            $table->jsonb('old_value')->nullable();
            $table->jsonb('new_value')->nullable();
            $table->timestamp('created_at')->useCurrent(); // INSERT only — no updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_log');
    }
};
