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
        Schema::create('entries', function (Blueprint $table) {
            $table->id();
            $table->dateTime('entry_datetime');
            $table->dateTime('exit_datetime')->nullable();
            $table->integer('total_minutes')->nullable();
            $table->string('status', 20)->default('active');
            $table->foreignId('entry_user_id')->nullable()->constrained('users');
            $table->foreignId('exit_user_id')->nullable()->constrained('users');
            $table->foreignId('branch_id')->constrained('branches');
            $table->foreignId('vehicle_id')->constrained('vehicles');
            $table->foreignId('entry_type_id')->constrained('entry_types');
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entries');
    }
};
