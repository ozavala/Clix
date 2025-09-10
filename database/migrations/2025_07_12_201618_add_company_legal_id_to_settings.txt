<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Agregar el setting company_legal_id
        Setting::create([
            'key' => 'company_legal_id',
            'value' => '0992793747-001',
            'type' => 'core',
            'is_editable' => false,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar el setting company_legal_id
        Setting::where('key', 'company_legal_id')->delete();
    }
};
