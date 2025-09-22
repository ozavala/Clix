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
        Schema::create('settings', function (Blueprint $table) {
            $table->id('setting_id');
            // Add tenant_id column with foreign key
            $table->foreignId('tenant_id')->constrained('tenants', 'tenant_id')->onDelete('cascade');
            // Modify columns for better configuration management
            $table->string('group')->default('general');
            $table->text('description')->nullable();
            $table->string('validation_rules')->nullable();
            $table->boolean('is_public')->default(false);
            
            // Update the value column to be a text field to support larger values
            $table->text('value')->change();
            
            // Add indexes
            $table->index(['tenant_id', 'key']);
            $table->index(['tenant_id', 'group']);
            
            $table->string('key');
            $table->string('value')->nullable();
            $table->enum('type', ['core', 'custom'])->default('custom');
            $table->boolean('is_editable')->default(false);
            $table->json('settings')->nullable();
            $table->timestamps();
        });
        // Create a default tenant for existing settings if needed
        if (!Schema::hasColumn('settings', 'tenant_id')) {
            $defaultTenant = DB::table('tenants')->first();
            if ($defaultTenant) {
                DB::table('settings')->update(['tenant_id' => $defaultTenant->id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
