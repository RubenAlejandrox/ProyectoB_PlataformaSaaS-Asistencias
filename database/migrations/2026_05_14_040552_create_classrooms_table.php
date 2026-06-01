<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('classrooms', function (Blueprint $table) {     
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->string('subject_name');
            $table->string('period');
            $table->char('grupo', 6);
            $table->unsignedSmallInteger('min_attendance_pct')->default(80);
            $table->unsignedSmallInteger('max_capacity')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['teacher_id', 'subject_name', 'period', 'grupo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classrooms');
    }
};
