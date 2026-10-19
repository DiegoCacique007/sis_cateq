<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla base del esquema académico. En una base existente ya está creada: solo se registra.
     * Sin telefono: lo añade 2026_05_02_181412 después de am.
     */
    public function up(): void
    {
        if (Schema::hasTable('tutores')) {
            return;
        }

        Schema::create('tutores', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->integer('id', true);
            $table->string('nombre', 100)->nullable();
            $table->string('ap', 100)->nullable();
            $table->string('am', 100)->nullable();
            $table->integer('alumno_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->unique(['nombre', 'ap', 'am', 'alumno_id'], 'uq_tutores_registro');
            $table->index('alumno_id', 'fk_tut_alu');
            $table->foreign('alumno_id', 'fk_tut_alu')->references('id')->on('alumnos');
        });
    }

    /**
     * Tabla base con datos: esta migración no se revierte, para que un rollback nunca la borre.
     */
    public function down(): void
    {
    }
};
