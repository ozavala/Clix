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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            //$table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('key');
            $table->string('value');
            $table->enum('type', ['core', 'custom'])->default('custom');
            $table->boolean('is_editable')->default(false);
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
