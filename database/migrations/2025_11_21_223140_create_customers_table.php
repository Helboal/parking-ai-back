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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('document_number', 50);
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->string('email', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('mobile', 20)->nullable();
            $table->text('address')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('photo_url', 255)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('document_type_id')->constrained('document_types');
            $table->timestamps();

            // Índice único compuesto: mismo documento puede existir con diferente tipo
            $table->unique(['document_type_id', 'document_number'], 'document_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
