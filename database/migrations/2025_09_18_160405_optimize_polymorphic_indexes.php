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
        // Optimize indexes for suppliers.noteable
        Schema::table('suppliers', function (Blueprint $table) {
            // Add individual indexes for specific query patterns
            $table->index('noteable_type', 'suppliers_noteable_type_index');
            $table->index('noteable_id', 'suppliers_noteable_id_index');
        });

        // Optimize indexes for personal_access_tokens.tokenable
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            // The composite index is already created by morphs()
            // Add individual indexes for specific query patterns
            $table->index('tokenable_type', 'personal_access_tokens_tokenable_type_index');
            $table->index('tokenable_id', 'personal_access_tokens_tokenable_id_index');
        });

        // Optimize indexes for warehouses.addressable
        Schema::table('warehouses', function (Blueprint $table) {
            // Add individual indexes for specific query patterns
            $table->index('addressable_type', 'warehouses_addressable_type_index');
            $table->index('addressable_id', 'warehouses_addressable_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the additional indexes in reverse order
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropIndex('warehouses_addressable_type_index');
            $table->dropIndex('warehouses_addressable_id_index');
        });

        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropIndex('personal_access_tokens_tokenable_type_index');
            $table->dropIndex('personal_access_tokens_tokenable_id_index');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropIndex('suppliers_noteable_type_index');
            $table->dropIndex('suppliers_noteable_id_index');
        });
    }
};
