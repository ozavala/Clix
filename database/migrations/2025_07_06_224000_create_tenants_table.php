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
        Schema::create('tenants', function (Blueprint $table) {
            $table->id('tenant_id');
            $table->string('name');
            /*$table->string('domain')->unique();
            $table->string('database')->unique();*/
            $table->string('legal_id')->unique();
            $table->boolean('is_active')->default(true);
            $table->string('subscription_plan', 50)->nullable();
            $table->timestamp('subscription_ends_at')->nullable();
            $table->json('settings')->nullable();           
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->string('logo')->nullable();
            $table->string('email')->nullable();
            $table->string('slogan')->nullable();
            $table->string('industry')->nullable();
            $table->softDeletes();
            $table->timestamps();
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
        Schema::dropIfExists('tenants');
    }
};
