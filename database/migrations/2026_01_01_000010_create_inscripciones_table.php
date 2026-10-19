<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla base del esquema académico. En una base existente ya está creada: solo se registra.
     * Igual que la base real: con asigna_grupo_id y sin columna estado.
     */
    public function up(): void
    {
        if (Schema::hasTable('inscripciones')) {
            return;
        }

        Schema::create('inscripciones', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->integer('id', true);
            $table->integer('alumno_id')->nullable();
            $table->integer('periodo_id')->nullable();
            $table->integer('grupo_id')->nullable();
            $table->integer('asigna_grupo_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->unique(['alumno_id', 'periodo_id', 'grupo_id'], 'uq_inscripciones_registro');
            $table->index('alumno_id', 'fk_ins_alu');
            $table->index('periodo_id', 'fk_ins_per');
            $table->index('grupo_id', 'fk_ins_gru');
            $table->foreign('alumno_id', 'fk_ins_alu')->references('id')->on('alumnos');
            $table->foreign('grupo_id', 'fk_ins_gru')->references('id')->on('grupos');
            $table->foreign('periodo_id', 'fk_ins_per')->references('id')->on('periodos');
        });
    }

    /**
     * Tabla base con datos: esta migración no se revierte, para que un rollback nunca la borre.
     */
    public function down(): void
    {
    }
};
