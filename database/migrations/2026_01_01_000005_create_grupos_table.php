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
        if (Schema::hasTable('grupos')) {
            return;
        }

        Schema::create('grupos', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->integer('id', true);
            $table->string('nombre', 50)->unique('uq_grupos_nombre');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();
            $table->integer('periodo_id')->nullable();

            $table->foreign('periodo_id', 'grupos_periodo_id_foreign')->references('id')->on('periodos')->nullOnDelete();
        });
    }

    /**
     * Tabla base con datos: esta migración no se revierte, para que un rollback nunca la borre.
     */
    public function down(): void
    {
    }
};
