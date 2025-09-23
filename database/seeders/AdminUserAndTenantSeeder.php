<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\CrmUser;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;


class AdminUserAndTenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default tenant
        $tenant = Tenant::firstOrCreate(
            ['name' => 'Default-2 Tenant'],
            [
                'legal_id' => '123456789',
                'is_active' => true,
                'subscription_plan' => 'enterprise',
                'subscription_ends_at' => now()->addYear(),
                'address' => [
                    'street' => '123 Main St, Anytown, USA',
                    'city' => 'Anytown',
                    'state' => 'CA',
                    'postal_code' => '12345',
                    'country' => 'EC'
                    ],
                'phone' => '+1 234 567 8900',
                'website' => 'https://example.com',
                'logo' => 'https://example.com/logo.png',
                'email' => 'admin@example.com',
                'slogan' => 'Your Business Partner',
                'industry' => 'Technology',
                'settings' => [
                    'timezone' => 'America/New_York',
                    'date_format' => 'm/d/Y',
                    'time_format' => 'h:i A',
                    'currency' => 'USD',
                    'features' => ['advanced_reporting', 'api_access'],
                ],
                                
                
                
                
                
                
            ]
        );

        // Create admin user
        $admin = CrmUser::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'username' => 'admin',
                'full_name' => 'Admin User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'tenant_id' => $tenant->id,
                'locale' => 'en',
            ]
        );

        // Assign admin role to the user
        $adminRole = DB::table('user_roles')->where('name', 'admin')->first();
        if ($adminRole) {
            $admin->roles()->attach($adminRole->id);
        }

        $this->command->info('Admin user created successfully!');
        $this->command->info('Email: admin@example.com');
        $this->command->info('Password: password');
        $this->command->info('Please change the password after first login.');
    }
}
