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
       Schema::create('permission_user_role', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->constrained('tenants', 'tenant_id')->onDelete('cascade');
            $table->foreignId('permission_id')->constrained('permissions', 'permission_id')->onDelete('cascade');
            $table->foreignId('role_id')->constrained('user_roles', 'role_id')->onDelete('cascade');
            $table->timestamps();
            
            $table->primary(['permission_id', 'role_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_user_role');
    }
};
