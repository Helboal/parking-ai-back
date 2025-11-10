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
        Schema::table('users', function (Blueprint $table) {
            $table->string('last_name', 80)->after('name');
            $table->string('document_number', 50)->after('last_name');
            $table->string('phone', 20)->nullable()->after('password');
            $table->boolean('is_active')->default(true)->after('phone');
            $table->foreignId('document_type_id')->after('is_active')->constrained('document_types');
            $table->softDeletes();

            // Índice único compuesto
            $table->unique(['document_type_id', 'document_number'], 'user_document_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('user_document_unique');
            $table->dropForeign(['document_type_id']);
            $table->dropColumn([
                'last_name',
                'document_number',
                'phone',
                'is_active',
                'document_type_id',
                'deleted_at',
            ]);
        });
    }
};
