<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bitácora del chatbot con mínimos datos: sin usuario, IP, ids de recursos, hora ni texto del mensaje.
 * Independiente del esquema académico: no tiene claves foráneas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatbot_events', function (Blueprint $table) {
            $table->id();
            $table->date('occurred_on')->index();
            $table->string('role', 30)->nullable();
            $table->string('intent', 40)->nullable();
            $table->string('resolution', 30)->nullable();
            $table->string('guard', 120)->nullable();
            $table->string('status', 30);
            $table->string('code', 60);
            $table->boolean('with_selection')->default(false);
            $table->string('message_shape', 200)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_events');
    }
};
