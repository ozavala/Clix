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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id('supplier_id');
            $table->foreignId('tenant_id')->constrained('tenants','tenant_id')->onDelete('cascade');
            $table->string('name');
            $table->string('legal_id',100)->nullable()->unique();
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('phone_number')->nullable();
            $table->morphs('noteable');
            $table->timestamps();
            $table->softDeletes();

            // Add individual indexes for specific query patterns
            $table->index('noteable_type', 'suppliers_noteable_type_index');
            $table->index('noteable_id', 'suppliers_noteable_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
