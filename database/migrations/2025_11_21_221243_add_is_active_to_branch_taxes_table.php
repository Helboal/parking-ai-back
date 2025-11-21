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
        Schema::table('branch_taxes', function (Blueprint $table) {
            // Agregar campo is_active según database.dbml
            $table->boolean('is_active')->default(true)->after('tax_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branch_taxes', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
