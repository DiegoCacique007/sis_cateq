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
        if (Schema::hasTable('unidades')) {
            return;
        }

        Schema::create('unidades', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->integer('id', true);
            $table->string('nombre', 100)->nullable();
            $table->integer('numero')->nullable();
            $table->integer('nivel_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->unique(['nivel_id', 'numero'], 'uq_unidades_numero_nivel');
            $table->unique(['nivel_id', 'nombre'], 'uq_unidades_nombre_nivel');
            $table->index('nivel_id', 'fk_uni_niv');
            $table->foreign('nivel_id', 'fk_uni_niv')->references('id')->on('niveles');
        });
    }

    /**
     * Tabla base con datos: esta migración no se revierte, para que un rollback nunca la borre.
     */
    public function down(): void
    {
    }
};
