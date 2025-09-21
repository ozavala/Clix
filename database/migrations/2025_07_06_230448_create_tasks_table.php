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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id('task_id');
            $table->foreignId('tenant_id')->constrained('tenants', 'tenant_id')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->string('status')->default('Pending');
            $table->string('priority')->default('Normal');
            $table->string('taskable_type');
            $table->unsignedBigInteger('taskable_id');
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('crm_users', 'user_id')->onDelete('set null');
            $table->foreignId('created_by_user_id')->constrained('crm_users', 'user_id')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['taskable_type', 'taskable_id']);
            $table->index(['tenant_id', 'taskable_type', 'taskable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
