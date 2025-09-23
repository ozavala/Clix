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
            'legal_id' => $this->faker->unique()->regexify('[A-Z]{2}[0-9]{10}'),
            'is_active' => true,
            'subscription_plan' => 'enterprise',
            'subscription_ends_at' => now()->addYear(),
            'address' => json_encode([  // Ensure address is encoded as JSON string
                'street' => $this->faker->streetAddress,
                'city' => $this->faker->city,
                'state' => $this->faker->state,
                'postal_code' => $this->faker->postcode,
                'country' => $this->faker->countryCode,
            ]),
            'phone' => $this->faker->phoneNumber,
            'website' => 'https://' . $this->faker->domainName,
            'logo' => $this->faker->imageUrl(200, 200, 'business'),
            'email' => $this->faker->companyEmail,
            'slogan' => $this->faker->catchPhrase,
            'industry' => $this->faker->word,
            'settings' => json_encode([  // Ensure settings is encoded as JSON string
                'timezone' => $this->faker->timezone,
                'date_format' => 'Y-m-d',
                'time_format' => 'H:i:s',
                'currency' => 'USD',
            ]),
        ];
    }
}