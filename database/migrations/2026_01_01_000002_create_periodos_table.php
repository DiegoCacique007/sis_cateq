<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla base del esquema académico. En una base existente ya está creada: solo se registra.
     */
    public function up(): void
    {
        if (Schema::hasTable('periodos')) {
            return;
        }

        Schema::create('periodos', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->integer('id', true);
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->boolean('estado')->nullable()->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->unique(['fecha_inicio', 'fecha_fin'], 'uq_periodos_fechas');
        });
    }

    /**
     * Tabla base con datos: esta migración no se revierte, para que un rollback nunca la borre.
     */
    public function down(): void
    {
    }
};
