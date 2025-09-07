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
        Schema::create('opportunities', function (Blueprint $table) {
            $table->id('opportunity_id');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('lead_id')->nullable()->constrained('leads', 'lead_id')->onDelete('set null');
            $table->foreignId('customer_id')->nullable()->constrained('customers', 'customer_id')->onDelete('set null');
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->string('stage')->default('Prospecting');
            $table->decimal('amount', 15, 2)->nullable();
            $table->date('expected_close_date')->nullable();
            $table->string('probability')->nullable();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('crm_users', 'user_id')->onDelete('set null');
            $table->foreignId('created_by_user_id')->nullable()->constrained('crm_users', 'user_id')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opportunities');
    }
};
