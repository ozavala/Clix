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
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id('goods_receipt_id');

            $table->foreignId('tenant_id')->constrained('tenants', 'tenant_id')->onDelete('cascade');
            $table->foreignId('purchase_order_id')->constrained('purchase_orders', 'purchase_order_id')->onDelete('cascade');
            $table->foreignId('received_by_user_id')->constrained('crm_users', 'user_id');
            $table->date('receipt_date');
            $table->text('notes')->nullable();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses', 'warehouse_id');
            $table->string('receipt_number')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
            
            $table->index(['purchase_order_id']);
            $table->index(['tenant_id', 'purchase_order_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipts');
    }
};
