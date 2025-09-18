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
        // Add columns only if they don't exist
        Schema::table('tenants', function (Blueprint $table) {
            // Check if columns exist before adding them
            if (!Schema::hasColumn('tenants', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('industry');
            }
            
            if (!Schema::hasColumn('tenants', 'subscription_plan')) {
                $table->string('subscription_plan', 50)->nullable()->after('is_active');
            }
            
            if (!Schema::hasColumn('tenants', 'subscription_ends_at')) {
                $table->timestamp('subscription_ends_at')->nullable()->after('subscription_plan');
            }
            
            if (!Schema::hasColumn('tenants', 'settings')) {
                $table->json('settings')->nullable()->after('subscription_ends_at');
            }
            
            // Add index - we'll use a try-catch to handle the case where it already exists
            try {
                $table->index(['is_active', 'subscription_plan'], 'tenants_is_active_subscription_plan_index');
            } catch (\Exception $e) {
                // Index already exists, we can ignore this error
                if (!str_contains($e->getMessage(), 'already exists')) {
                    throw $e;
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We won't drop columns in the down method to prevent data loss
        // This is a safety measure since we're adding columns that might contain data
        
        // If you need to rollback, create a new migration to handle the rollback
        // after checking the impact on your application
    }
};
