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
            if (!Schema::hasColumn('crm_users', 'locale')) {
                $table->string('locale', 10)->default('en')->after('email_verified_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crm_users', function (Blueprint $table) {
            if (Schema::hasColumn('crm_users', 'locale')) {
                $table->dropColumn('locale');
            }
        });
    }
};
