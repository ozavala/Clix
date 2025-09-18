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
        Schema::create('tenant_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('crm_users', 'user_id')->onDelete('cascade');
            $table->boolean('is_owner')->default(false);
            $table->timestamps();
            
            // Ensure a user can only be associated with a tenant once
            $table->unique(['tenant_id', 'user_id']);
            
            // Index for faster lookups
            $table->index(['user_id', 'is_owner']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_user');
    }
};
