<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Campos de sacramentos en alumnos ───
        Schema::table('alumnos', function (Blueprint $table) {
            // Datos de Bautizo
            $table->string('bautizo_lugar', 255)->nullable()->after('estado');
            $table->date('bautizo_fecha')->nullable()->after('bautizo_lugar');
            $table->string('bautizo_libro', 100)->nullable()->after('bautizo_fecha');
            $table->string('bautizo_acta', 100)->nullable()->after('bautizo_libro');

            // Datos de Primera Comunión
            $table->string('primera_comunion_lugar', 255)->nullable()->after('bautizo_acta');
            $table->date('primera_comunion_fecha')->nullable()->after('primera_comunion_lugar');
            $table->string('primera_comunion_libro', 100)->nullable()->after('primera_comunion_fecha');
            $table->string('primera_comunion_acta', 100)->nullable()->after('primera_comunion_libro');
        });

        // ─── Comunidad asignada al usuario (para coord_comunidad) ───
        if (!Schema::hasColumn('users', 'comunidad_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('comunidad_id')->nullable()->after('role');
            });
        }
    }

    public function down(): void
    {
        Schema::table('alumnos', function (Blueprint $table) {
            $table->dropColumn([
                'bautizo_lugar',
                'bautizo_fecha',
                'bautizo_libro',
                'bautizo_acta',
                'primera_comunion_lugar',
                'primera_comunion_fecha',
                'primera_comunion_libro',
                'primera_comunion_acta',
            ]);
        });

        if (Schema::hasColumn('users', 'comunidad_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('comunidad_id');
            });
        }
    }
};
