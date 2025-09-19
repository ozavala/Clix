<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TaxPayment;
use App\Models\TaxCollection;
use App\Models\PurchaseOrder;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\User;
use App\Services\TaxRecoveryService;

class TaxDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding tax data for the last 2 years...');

        // Get existing data
        $purchaseOrders = PurchaseOrder::where('tax_amount', '>', 0)->get();
        $invoices = Invoice::where('tax_amount', '>', 0)->get();
        $quotations = Quotation::where('tax_amount', '>', 0)->get();
        $user = User::first() ?? User::factory()->create();

        $taxRecoveryService = new TaxRecoveryService();

        // Register tax payments from existing purchase orders
        foreach ($purchaseOrders as $po) {
            if (!$po->taxPayment) {
                $taxRecoveryService->registerTaxPayment($po);
            }
        }

        // Register tax collections from existing invoices
        foreach ($invoices as $invoice) {
            if (!$invoice->taxCollection) {
                $taxRecoveryService->registerTaxCollection($invoice);
            }
        }

        // Register tax collections from existing quotations
        foreach ($quotations as $quotation) {
            if (!$quotation->taxCollection) {
                $taxRecoveryService->registerTaxCollectionFromQuotation($quotation);
            }
        }

        // Create additional tax payments for the last 2 years (more realistic distribution)
        $this->createTaxPaymentsForPeriod($user);

        // Create additional tax collections for the last 2 years (more realistic distribution)
        $this->createTaxCollectionsForPeriod($user);

        $this->command->info('✅ Tax data seeded successfully!');
    }

    private function createTaxPaymentsForPeriod(User $user): void
    {
        $startDate = now()->subYears(2);
        $endDate = now();

        // Create more payments in recent months
        for ($i = 0; $i < 60; $i++) {
            $date = $startDate->copy()->addDays($i * 12); // Every 12 days
            if ($date->isAfter($endDate)) break;

            // More payments in recent months
            $multiplier = $date->isAfter(now()->subMonths(6)) ? 2 : 1;
            
            for ($j = 0; $j < $multiplier; $j++) {
                TaxPayment::factory()->create([
                    'payment_date' => $date,
                    'created_by_user_id' => $user->user_id,
                ]);
            }
        }
    }

    private function createTaxCollectionsForPeriod(User $user): void
    {
        $startDate = now()->subYears(2);
        $endDate = now();

        // Create more collections in recent months
        for ($i = 0; $i < 80; $i++) {
            $date = $startDate->copy()->addDays($i * 9); // Every 9 days
            if ($date->isAfter($endDate)) break;

            // More collections in recent months
            $multiplier = $date->isAfter(now()->subMonths(6)) ? 3 : 1;
            
            for ($j = 0; $j < $multiplier; $j++) {
                TaxCollection::factory()->create([
                    'collection_date' => $date,
                    'created_by_user_id' => $user->user_id,
                ]);
            }
        }
    }
}
