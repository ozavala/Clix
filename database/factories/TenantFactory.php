<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company,
            'legal_id' => $this->faker->unique()->bothify('##-########-001'),
            'is_active' => true,
            'address' => $this->faker->address,
            'phone' => $this->faker->phoneNumber,
            'website' => $this->faker->url,
            'logo' => $this->faker->imageUrl(),
            'email' => $this->faker->email,
            'industry' => $this->faker->word,
        
            /*'settings' => [
                'default_currency' => 'USD',
                'default_locale' => 'en',
                'tax_includes_services' => true,
                'tax_includes_transport' => false,
            ],*/
        ];
    }
}