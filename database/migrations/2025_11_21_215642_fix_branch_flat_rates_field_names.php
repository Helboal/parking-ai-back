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
        Schema::table('branch_flat_rates', function (Blueprint $table) {
            // Renombrar campos según database.dbml
            $table->renameColumn('minuts_threshold', 'minutes_threshold');
            $table->renameColumn('flat_rate', 'flat_rate_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branch_flat_rates', function (Blueprint $table) {
            // Revertir nombres en caso de rollback
            $table->renameColumn('minutes_threshold', 'minuts_threshold');
            $table->renameColumn('flat_rate_amount', 'flat_rate');
        });
    }
};
