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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id('transaction_id');
            $table->foreignId('tenant_id')->constrained('tenants', 'tenant_id')->onDelete('cascade');
            $table->string('type');
            $table->date('date');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('bill_id')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('created_by_user_id');
            $table->timestamps();

            
            $table->foreign('supplier_id')->references('supplier_id')->on('suppliers');
            $table->foreign('customer_id')->references('customer_id')->on('customers');
            $table->foreign('invoice_id')->references('invoice_id')->on('invoices');
            $table->foreign('bill_id')->references('bill_id')->on('bills');
            $table->foreign('payment_id')->references('payment_id')->on('payments');
            $table->foreign('journal_entry_id')->references('journal_entry_id')->on('journal_entries');
            $table->foreign('created_by_user_id')->references('user_id')->on('crm_users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
