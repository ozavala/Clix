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
        Schema::create('crm_user_user_role', function (Blueprint $table) {
            $table->foreignId('tenant_id')->constrained('tenants', 'tenant_id')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('crm_users', 'user_id')->onDelete('cascade');
            $table->foreignId('role_id')->constrained('user_roles', 'role_id')->onDelete('cascade');
            $table->timestamps();
            
            $table->primary(['tenant_id', 'user_id', 'role_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_user_user_role');
    }
};
