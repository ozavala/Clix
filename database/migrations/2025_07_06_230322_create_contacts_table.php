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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id('contact_id');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->string('title')->nullable();
            $table->foreignId('created_by_user_id')->constrained('crm_users', 'user_id');
            $table->string('contactable_type');
            $table->unsignedBigInteger('contactable_id');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['contactable_type', 'contactable_id']);
            $table->index(['tenant_id', 'contactable_type', 'contactable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
