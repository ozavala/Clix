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
        Schema::create('marketing_campaigns', function (Blueprint $table) {
            $table->id('campaign_id');
            $table->foreignId('tenant_id')->constrained('tenants', 'tenant_id')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('subject');
            $table->text('content');
            $table->enum('status', ['draft', 'scheduled', 'sending', 'sent', 'paused', 'cancelled'])->default('draft');
            $table->enum('type', ['email', 'newsletter', 'promotional', 'announcement'])->default('email');
            $table->foreignId('email_template_id')->nullable()->constrained('email_templates', 'email_template_id')->onDelete('set null');
            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')->references('user_id')->on('crm_users')->onDelete('cascade');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->integer('total_recipients')->default(0);
            $table->integer('sent_count')->default(0);
            $table->integer('opened_count')->default(0);
            $table->integer('clicked_count')->default(0);
            $table->integer('bounced_count')->default(0);
            $table->integer('unsubscribed_count')->default(0);
            $table->json('target_audience')->nullable(); // Filtros para audiencia objetivo
            $table->json('settings')->nullable(); // Configuraciones adicionales
            $table->timestamps();
            
            $table->index(['status', 'scheduled_at']);
            $table->index(['type', 'created_at']);
            $table->index(['tenant_id', 'status', 'scheduled_at']);
            $table->index(['tenant_id', 'type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketing_campaigns');
    }
};
