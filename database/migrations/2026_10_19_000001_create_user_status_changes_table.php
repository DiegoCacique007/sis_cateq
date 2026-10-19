<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Historial de cambios de cuenta hechos por Secretaría (bloquear, reactivar, cambiar rol o comunidad).
     * Sin claves foráneas, como chatbot_events: el historial se conserva aunque cambien las cuentas.
     */
    public function up(): void
    {
        Schema::create('user_status_changes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('changed_by')->nullable()->index();
            $table->string('action', 30);
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20)->nullable();
            $table->string('from_role', 30)->nullable();
            $table->string('to_role', 30)->nullable();
            $table->unsignedBigInteger('from_comunidad_id')->nullable();
            $table->unsignedBigInteger('to_comunidad_id')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_status_changes');
    }
};
