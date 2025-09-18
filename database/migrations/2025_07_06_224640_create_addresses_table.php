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
        Schema::create('addresses', function (Blueprint $table) {
            $table->id('address_id');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('addressable_type');
            $table->unsignedBigInteger('addressable_id');
            $table->string('address_type')->nullable()->comment('e.g., Billing, Shipping, Primary, Mailing');
            $table->string('street_address_line_1');
            $table->string('street_address_line_2')->nullable();
            $table->string('city');
            $table->string('state_province')->nullable();
            $table->string('postal_code');
            $table->string('country_code', 2)->default('US');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            
            $table->index(['addressable_type', 'addressable_id']);
            $table->index(['tenant_id', 'addressable_type', 'addressable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
