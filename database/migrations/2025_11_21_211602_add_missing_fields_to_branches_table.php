<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            // Agregar campos faltantes según database.dbml
            $table->string('code', 20)->unique()->after('name');
            $table->string('email', 100)->nullable()->after('phone');
            $table->time('opening_time')->nullable()->after('email');
            $table->time('closing_time')->nullable()->after('opening_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['code', 'email', 'opening_time', 'closing_time']);
        });
    }
};
