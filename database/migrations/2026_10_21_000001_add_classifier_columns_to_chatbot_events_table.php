<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Resultado del clasificador de intenciones (modo sombra o assist) en la bitácora del chatbot.
     * Solo códigos: nunca el texto del mensaje, el prompt ni la respuesta del modelo.
     */
    public function up(): void
    {
        Schema::table('chatbot_events', function (Blueprint $table) {
            if (! Schema::hasColumn('chatbot_events', 'classifier_intent')) {
                $table->string('classifier_intent', 40)->nullable();
                $table->string('classifier_outcome', 30)->nullable();
                $table->unsignedInteger('classifier_latency_ms')->nullable();
                $table->string('prompt_version', 20)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('chatbot_events', function (Blueprint $table) {
            if (Schema::hasColumn('chatbot_events', 'classifier_intent')) {
                $table->dropColumn(['classifier_intent', 'classifier_outcome', 'classifier_latency_ms', 'prompt_version']);
            }
        });
    }
};
