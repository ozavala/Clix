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
        // List of tables that need tenant_id
        $tables = [
            'customers',
            'invoices',
            'bills',
            'products',
            'orders',
            'purchase_orders',
            'payments',
            'journal_entries',
            'leads',
            'opportunities',
            'quotations',
            'contacts',
            'addresses',
            'notes',
            'tasks',
            'marketing_campaigns',
            'email_templates',
            'goods_receipts',
            'landed_costs',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $blueprint) use ($tableName) {
                    if (!Schema::hasColumn($tableName, 'tenant_id')) {
                        $blueprint->foreignId('tenant_id')
                            ->nullable()
                            ->constrained()
                            ->onDelete('cascade');
                        
                        // Add index for better query performance
                        $blueprint->index('tenant_id');
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // List of tables that had tenant_id added
        $tables = [
            'customers',
            'invoices',
            'bills',
            'products',
            'orders',
            'purchase_orders',
            'payments',
            'journal_entries',
            'leads',
            'opportunities',
            'quotations',
            'contacts',
            'addresses',
            'notes',
            'tasks',
            'marketing_campaigns',
            'email_templates',
            'goods_receipts',
            'landed_costs',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'tenant_id')) {
                Schema::table($tableName, function (Blueprint $blueprint) use ($tableName) {
                    // Drop foreign key constraint first
                    $blueprint->dropForeign([$tableName . '_tenant_id_foreign']);
                    // Drop the index
                    $blueprint->dropIndex([$tableName . '_tenant_id_index']);
                    // Drop the column
                    $blueprint->dropColumn('tenant_id');
                });
            }
        }
    }
};
