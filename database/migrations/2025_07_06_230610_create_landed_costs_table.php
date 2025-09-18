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
        Schema::create('landed_costs', function (Blueprint $table) {
            $table->id('landed_cost_id');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('costable_type');
            $table->unsignedBigInteger('costable_id');
            $table->string('description');
            $table->decimal('amount', 15, 2);
            $table->timestamps();
            
            $table->index(['costable_type', 'costable_id']);
            $table->index(['tenant_id', 'costable_type', 'costable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landed_costs');
    }
};
