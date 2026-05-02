<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('grupos')) {
            Schema::table('grupos', function (Blueprint $table) {
                if (!Schema::hasColumn('grupos', 'periodo_id')) {
                    $table->integer('periodo_id')->nullable();
                    $table->foreign('periodo_id')->references('id')->on('periodos')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('evaluaciones')) {
            Schema::table('evaluaciones', function (Blueprint $table) {
                if (!Schema::hasColumn('evaluaciones', 'periodo_id')) {
                    $table->integer('periodo_id')->nullable();
                    $table->foreign('periodo_id')->references('id')->on('periodos')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('grupos')) {
            Schema::table('grupos', function (Blueprint $table) {
                if (Schema::hasColumn('grupos', 'periodo_id')) {
                    $table->dropForeign(['periodo_id']);
                    $table->dropColumn('periodo_id');
                }
            });
        }

        if (Schema::hasTable('evaluaciones')) {
            Schema::table('evaluaciones', function (Blueprint $table) {
                if (Schema::hasColumn('evaluaciones', 'periodo_id')) {
                    $table->dropForeign(['periodo_id']);
                    $table->dropColumn('periodo_id');
                }
            });
        }
    }
};
