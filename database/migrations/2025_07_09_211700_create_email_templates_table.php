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
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name');
            $table->string('subject');
            $table->text('content');
            $table->text('html_content')->nullable();
            $table->enum('type', ['newsletter', 'promotional', 'welcome', 'notification', 'custom'])->default('custom');
            $table->json('variables')->nullable(); // Variables disponibles en la plantilla
            $table->json('settings')->nullable(); // Configuraciones de la plantilla
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')->references('user_id')->on('crm_users')->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['type', 'is_active']);
            $table->index(['tenant_id', 'type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
