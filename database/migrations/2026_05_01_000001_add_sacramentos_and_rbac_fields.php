<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Los campos de sacramentos de alumnos (bautizo_* y primera_comunion_*) se quitaron de esta
        // migración: la base real no los tiene y ningún código los usa. Ya consta como ejecutada en la
        // base real, así que este cambio solo afecta a las instalaciones nuevas.

        // ─── Comunidad asignada al usuario (para coord_comunidad) ───
        if (!Schema::hasColumn('users', 'comunidad_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('comunidad_id')->nullable()->after('role');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'comunidad_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('comunidad_id');
            });
        }
    }
};
