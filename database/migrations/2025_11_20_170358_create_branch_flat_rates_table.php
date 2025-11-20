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
        Schema::create('branch_flat_rates', function (Blueprint $table) {
            $table->id();
            $table->integer('minuts_threshold');
            $table->decimal('flat_rate', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('vehicle_type_id')->constrained('vehicle_types')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_flat_rates');
    }
};
