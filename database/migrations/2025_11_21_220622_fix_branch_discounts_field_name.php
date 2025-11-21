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
        Schema::table('branch_discounts', function (Blueprint $table) {
            // Renombrar campo según database.dbml (corregir typo)
            $table->renameColumn('minuts', 'minutes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branch_discounts', function (Blueprint $table) {
            // Revertir nombre en caso de rollback
            $table->renameColumn('minutes', 'minuts');
        });
    }
};
