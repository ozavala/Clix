<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Quotation;
use App\Models\Opportunity;
use App\Models\User;

class QuotationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $opportunities = Opportunity::all();
        $users = User::all();

        if ($opportunities->isEmpty() || $users->isEmpty()) {
            $this->command->info('Skipping QuotationSeeder: Missing Opportunities or Users to create quotations for.');
            return;
        }

        // Create a quotation for each opportunity
        foreach ($opportunities as $opportunity) {
            Quotation::factory()->create([
                'opportunity_id' => $opportunity->opportunity_id,
                'created_by_user_id' => $users->random()->user_id,
                'subject' =>  $opportunity->description,
                'quotation_date' => now(),
                'expiry_date' => now()->addDays(30),
                'status' => 'Draft',
                'subtotal' => $opportunity->value ? $opportunity->value : 0,
                // Ensure subtotal is not negative
                'total_amount' => $opportunity->value ? $opportunity->value : 0,
                'discount_type' => 'Percentage',
                'discount_value' => 10, // Example discount value
                'discount_amount' => $opportunity->value * 0.10, // 10% discount
                'tax_percentage' => 15, // Example tax percentage
                'tax_amount' => ($opportunity->value - ($opportunity->value * 0.10)) * 0.15, // 15% tax on discounted value)
            ]);
        }
        
        $this->command->info('Quotations created from opportunities.');
    }
}