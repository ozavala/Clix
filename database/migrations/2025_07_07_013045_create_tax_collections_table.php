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
        Schema::create('tax_collections', function (Blueprint $table) {
            $table->id('tax_collection_id');
            $table->foreignId('tenant_id')->constrained('tenants', 'tenant_id')->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices', 'invoice_id')->onDelete('cascade');
            $table->foreignId('quotation_id')->nullable()->constrained('quotations', 'quotation_id')->onDelete('cascade');
            $table->foreignId('tax_rate_id')->constrained('tax_rates', 'tax_rate_id')->onDelete('cascade');
            $table->decimal('taxable_amount', 15, 2); // Amount before tax
            $table->decimal('tax_amount', 15, 2); // Amount IVA received
            $table->string('collection_type')->default('sale'); // 'sale', 'service'
            $table->date('collection_date');
            $table->string('customer_name')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('collected'); // 'collected', 'pending', 'refunded'
            $table->date('remittance_date')->nullable(); // Date of IVA remittance
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
        Schema::dropIfExists('tax_collections');
    }
};
