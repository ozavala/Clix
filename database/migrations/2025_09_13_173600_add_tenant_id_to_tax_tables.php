<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tax_payments', 'tenant_id')) {
            Schema::table('tax_payments', function (Blueprint $table) {
                $table->foreignId('tenant_id')->after('tax_payment_id')->constrained('tenants', 'id')->cascadeOnDelete();
            });
        }

        if (!Schema::hasColumn('tax_collections', 'tenant_id')) {
            Schema::table('tax_collections', function (Blueprint $table) {
                $table->foreignId('tenant_id')->after('tax_collection_id')->constrained('tenants', 'id')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tax_payments', 'tenant_id')) {
            Schema::table('tax_payments', function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
                $table->dropColumn('tenant_id');
            });
        }

        if (Schema::hasColumn('tax_collections', 'tenant_id')) {
            Schema::table('tax_collections', function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
                $table->dropColumn('tenant_id');
            });
        }
    }
};
