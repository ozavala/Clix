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
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id('email_logs_id');
            $table->foreignId('campaign_id')->nullable()->constrained('marketing_campaigns', 'campaign_id')->onDelete('cascade');
            $table->foreignId('recipient_id')->nullable()->constrained('campaign_recipients', 'recipients_id')->onDelete('cascade');
            $table->string('email');
            $table->string('subject');
            $table->enum('type', ['campaign', 'notification', 'system'])->default('campaign');
            $table->enum('status', ['sent', 'delivered', 'opened', 'clicked', 'bounced', 'failed'])->default('sent');
            $table->text('content')->nullable();
            $table->json('metadata')->nullable(); // Metadatos adicionales
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('bounced_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index(['campaign_id', 'status']);
            $table->index(['email', 'sent_at']);
            $table->index(['type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
