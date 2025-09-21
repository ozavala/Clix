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
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id('warehouse_id');
            $table->foreignId('tenant_id')->constrained('tenants', 'tenant_id')->onDelete('cascade');
            $table->string('name');
            $table->string('location')->nullable();
            $table->morphs('addressable');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

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
        Schema::dropIfExists('warehouses');
    }
};
