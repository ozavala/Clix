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
            'address' => json_encode([
                'street' => $this->faker->streetAddress,
                'city' => $this->faker->city,
                'state' => $this->faker->state,
                'postal_code' => $this->faker->postcode,
                'country' => $this->faker->countryCode,
            ]),
            'phone' => $this->faker->phoneNumber,
            'website' => 'https://' . $this->faker->domainName,
            'logo' => $this->faker->imageUrl(200, 200, 'business'),
            'email' => 'info@' . $this->faker->safeEmailDomain,
            'industry' => $this->faker->word,
            'settings' => [
                'currency' => 'USD',
                'locale' => 'en',
                'timezone' => $this->faker->timezone,
                'date_format' => 'Y-m-d',
                'time_format' => 'H:i',
                'tax_includes_services' => true,
                'tax_includes_transport' => false,
            ],
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}