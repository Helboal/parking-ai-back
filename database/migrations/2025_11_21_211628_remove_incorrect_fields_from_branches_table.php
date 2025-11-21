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
            // Eliminar campos que no corresponden según database.dbml
            // La capacidad está en branch_parking_capacity
            $table->dropColumn(['total_spaces', 'available_spaces']);

            // La relación usuario-sucursal está en user_branches
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            // Restaurar campos en caso de rollback
            $table->integer('total_spaces')->after('closing_time');
            $table->integer('available_spaces')->after('total_spaces');
            $table->foreignId('user_id')->nullable()->after('is_active')->constrained('users');
        });
    }
};
