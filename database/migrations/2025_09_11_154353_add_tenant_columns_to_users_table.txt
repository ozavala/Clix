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
        Schema::table('crm_users', function (Blueprint $table) {
            // Tenant ID is already defined in the crm_users table
            // Just add the is_super_admin column
            $table->boolean('is_super_admin')
                ->default(false)
                ->after('remember_token');
                
            $table->index(['tenant_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crm_users', function (Blueprint $table) {
            $table->dropColumn('is_super_admin');
        });
    }
};
