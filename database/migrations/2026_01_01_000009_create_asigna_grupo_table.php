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
        if (Schema::hasTable('asigna_grupo')) {
            return;
        }

        Schema::create('asigna_grupo', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->integer('id', true);
            $table->integer('comunidad_id')->nullable();
            $table->integer('grupo_id')->nullable();
            $table->integer('nivel_id')->nullable();
            $table->integer('periodo_id')->nullable();
            $table->unsignedBigInteger('catequista_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->unique(['comunidad_id', 'grupo_id', 'nivel_id', 'periodo_id', 'catequista_id'], 'uq_asigna_grupo_registro');
            $table->index('comunidad_id', 'fk_asig_com');
            $table->index('grupo_id', 'fk_asig_gru');
            $table->index('nivel_id', 'fk_asig_niv');
            $table->index('periodo_id', 'fk_asig_per');
            $table->index('catequista_id', 'fk_asig_cat');
            $table->foreign('catequista_id', 'fk_asig_cat')->references('id')->on('users');
            $table->foreign('comunidad_id', 'fk_asig_com')->references('id')->on('comunidades');
            $table->foreign('grupo_id', 'fk_asig_gru')->references('id')->on('grupos');
            $table->foreign('nivel_id', 'fk_asig_niv')->references('id')->on('niveles');
            $table->foreign('periodo_id', 'fk_asig_per')->references('id')->on('periodos');
        });
    }

    /**
     * Tabla base con datos: esta migración no se revierte, para que un rollback nunca la borre.
     */
    public function down(): void
    {
    }
};
