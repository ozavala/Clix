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
        Schema::create('notes', function (Blueprint $table) {
            $table->id('note_id');
            $table->foreignId('tenant_id')->constrained('tenants','tenant_id')->onDelete('cascade');
            $table->text('body');
            $table->string('noteable_type');
            $table->unsignedBigInteger('noteable_id');
            $table->foreignId('created_by_user_id')->constrained('crm_users', 'user_id');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['noteable_type', 'noteable_id']);
            $table->index(['tenant_id', 'noteable_type', 'noteable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
