<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // si ya tienes role, esto solo agrega status/aprobación
            $table->string('status', 20)->default('pendiente')->after('role');
            $table->timestamp('approved_at')->nullable()->after('status');
            $table->unsignedBigInteger('approved_by')->nullable()->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['status','approved_at','approved_by']);
        });
    }
};