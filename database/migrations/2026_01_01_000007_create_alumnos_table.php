<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla base del esquema académico. En una base existente ya está creada: solo se registra.
     * Sin fecha_nacimiento: la añade 2026_05_01_220538 después de estado.
     */
    public function up(): void
    {
        if (Schema::hasTable('alumnos')) {
            return;
        }

        Schema::create('alumnos', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->integer('id', true);
            $table->string('nombre', 100)->nullable();
            $table->string('apellido_paterno', 100)->nullable();
            $table->string('apellido_materno', 100)->nullable();
            $table->integer('comunidad_id')->nullable();
            $table->boolean('estado')->nullable()->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->unique(['nombre', 'apellido_paterno', 'apellido_materno', 'comunidad_id'], 'uq_alumnos_registro');
            $table->index('comunidad_id', 'fk_alu_com');
            $table->foreign('comunidad_id', 'fk_alu_com')->references('id')->on('comunidades');
        });
    }

    /**
     * Tabla base con datos: esta migración no se revierte, para que un rollback nunca la borre.
     */
    public function down(): void
    {
    }
};
