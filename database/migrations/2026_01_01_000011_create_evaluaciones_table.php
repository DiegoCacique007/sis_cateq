<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla base del esquema académico. En una base existente ya está creada: solo se registra.
     * Incluye periodo_id y su FK: 2026_04_30_145411 comprueba la columna y no la repite.
     */
    public function up(): void
    {
        if (Schema::hasTable('evaluaciones')) {
            return;
        }

        Schema::create('evaluaciones', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->integer('id', true);
            $table->integer('inscripcion_id')->nullable();
            $table->integer('rubro_id')->nullable();
            $table->decimal('calificacion', 5, 2)->nullable();
            $table->integer('unidad_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();
            $table->integer('periodo_id')->nullable();

            $table->unique(['inscripcion_id', 'rubro_id', 'unidad_id'], 'uq_evaluaciones_registro');
            $table->index('inscripcion_id', 'fk_eva_ins');
            $table->index('rubro_id', 'fk_eva_rub');
            $table->index('unidad_id', 'fk_eva_uni');
            $table->foreign('periodo_id', 'evaluaciones_periodo_id_foreign')->references('id')->on('periodos')->nullOnDelete();
            $table->foreign('inscripcion_id', 'fk_eva_ins')->references('id')->on('inscripciones');
            $table->foreign('rubro_id', 'fk_eva_rub')->references('id')->on('rubros');
            $table->foreign('unidad_id', 'fk_eva_uni')->references('id')->on('unidades');
        });
    }

    /**
     * Tabla base con datos: esta migración no se revierte, para que un rollback nunca la borre.
     */
    public function down(): void
    {
    }
};
