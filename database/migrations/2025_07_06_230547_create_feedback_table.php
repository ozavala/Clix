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
        Schema::create('feedback', function (Blueprint $table) {
            $table->id('feedback_id');
            $table->foreignId('tenant_id')->constrained('tenants', 'tenant_id')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('crm_users', 'user_id')->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->enum('type', ['Bug Report', 'Feature Request', 'Suggestion'])->default('Suggestion');
            $table->enum('status', ['New', 'In Progress', 'Completed', 'Wont Fix'])->default('New');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};
