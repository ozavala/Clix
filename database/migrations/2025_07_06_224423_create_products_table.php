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
        Schema::create('products', function (Blueprint $table) {
            $table->id('product_id');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('sku', 100)->nullable()->unique();
            $table->decimal('price', 15, 2)->default(0.00);
            $table->decimal('cost', 15, 2)->nullable();
            $table->integer('quantity_on_hand')->default(0);
            $table->integer('reorder_point')->default(10);
            $table->boolean('is_service')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates', 'tax_rate_id')->onDelete('set null');
            $table->boolean('is_taxable')->default(true);
            $table->decimal('tax_rate_percentage', 5, 2)->nullable();            
            $table->string('tax_category')->nullable(); // 'goods', 'services', 'transport', 'insurance', 'storage'
            $table->string('tax_country_code', 3)->default('EC'); // Código de país para IVA
            $table->foreignId('created_by_user_id')->nullable()->constrained('crm_users', 'user_id')->onDelete('set null');
            $table->foreignId('category_id')->nullable()->constrained('product_categories', 'category_id')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'sku']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
