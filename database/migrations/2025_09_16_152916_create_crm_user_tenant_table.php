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
        Schema::create('crm_user_tenant', function (Blueprint $table) {
           
            $table->foreignId('user_id')->constrained('crm_users', 'user_id')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('tenants','tenant_id')->onDelete('cascade');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            
            $table->primary(['user_id', 'tenant_id']);
            $table->unique(['user_id', 'tenant_id']);
            $table->index(['user_id', 'tenant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_user_tenant');
    }
};
