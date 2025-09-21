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
        Schema::create('quotations', function (Blueprint $table) {
            $table->id('quotation_id');
            $table->string('quotation_number')->unique();
            $table->foreignId('tenant_id')->constrained('tenants', 'tenant_id')->onDelete('cascade');
            $table->foreignId('opportunity_id')->constrained('opportunities', 'opportunity_id')->onDelete('cascade');
            $table->string('subject');
            $table->date('quotation_date');
            $table->date('expiry_date')->nullable();
            $table->string('status')->default('Draft');
            $table->decimal('subtotal', 15, 2)->default(0.00);
            $table->string('discount_type')->nullable();
            $table->decimal('discount_value', 15, 2)->nullable();
            $table->decimal('discount_amount', 15, 2)->default(0.00);
            $table->decimal('tax_percentage', 5, 2)->nullable();
            $table->decimal('tax_amount', 15, 2)->default(0.00);
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates', 'tax_rate_id')->onDelete('set null');
            $table->decimal('total_amount', 15, 2)->default(0.00);
            $table->text('terms_and_conditions')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('crm_users', 'user_id')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'opportunity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
