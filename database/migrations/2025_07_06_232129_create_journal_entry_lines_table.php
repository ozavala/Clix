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
        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id('journal_entry_line_id');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('journal_entry_id')->constrained('journal_entries', 'journal_entry_id')->onDelete('cascade');
            $table->string('account_code')->index();
            $table->string('account_name')->nullable();
            $table->decimal('debit_amount', 15, 2)->default(0.00);
            $table->decimal('credit_amount', 15, 2)->default(0.00);
            $table->string('entity_type')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index(['entity_type', 'entity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entry_lines');
    }
};
