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
        Schema::create('branch_parking_capacity', function (Blueprint $table) {
            $table->id();
            $table->integer('total_spaces');
            $table->integer('occupied_spaces')->default(0);
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('vehicle_type_id')->constrained('vehicle_types')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['branch_id', 'vehicle_type_id'], 'branch_vehicle_capacity_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_parking_capacity');
    }
};
