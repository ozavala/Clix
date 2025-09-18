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
        Schema::create('tax_payments', function (Blueprint $table) {
            $table->id('tax_payment_id');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders', 'purchase_order_id')->onDelete('cascade');
            $table->foreignId('invoice_id')->nullable()->constrained('invoices', 'invoice_id')->onDelete('cascade');
            $table->foreignId('tax_rate_id')->constrained('tax_rates', 'tax_rate_id')->onDelete('cascade');
            $table->decimal('taxable_amount', 15, 2); // Monto sobre el cual se calcula el IVA
            $table->decimal('tax_amount', 15, 2); // Monto del IVA pagado
            $table->string('payment_type')->default('import'); // 'import', 'purchase', 'service'
            $table->date('payment_date');
            $table->string('document_number')->nullable(); // Número de factura del proveedor
            $table->string('supplier_name')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('paid'); // 'paid', 'pending', 'recovered'
            $table->date('recovery_date')->nullable(); // Fecha cuando se recuperó el IVA
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
        Schema::dropIfExists('tax_payments');
    }
};
