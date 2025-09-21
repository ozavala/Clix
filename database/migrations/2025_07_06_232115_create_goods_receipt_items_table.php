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
        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->id('goods_receipt_item_id');
            $table->foreignId('tenant_id')->constrained('tenants', 'tenant_id')->onDelete('cascade');
            $table->foreignId('goods_receipt_id')->constrained('goods_receipts', 'goods_receipt_id')->onDelete('cascade');
            $table->foreignId('purchase_order_item_id')->constrained('purchase_order_items', 'purchase_order_item_id');
            $table->foreignId('product_id')->constrained('products', 'product_id');
            $table->integer('quantity_received');
            $table->decimal('unit_cost_with_landed', 10, 2)->nullable();
            $table->decimal('total_cost', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_items');
    }
};
