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
            $table->id();
            $table->unsignedBigInteger('crm_user_id');
            $table->unsignedBigInteger('tenant_id');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->foreign('crm_user_id')
                ->references('user_id')
                ->on('crm_users')
                ->onDelete('cascade');

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');

            $table->unique(['crm_user_id', 'tenant_id']);
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
