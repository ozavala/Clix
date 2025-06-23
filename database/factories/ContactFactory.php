<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\Customer;
use App\Models\CrmUser;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactFactory extends Factory
{
    protected $model = Contact::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->unique()->safeEmail,
            'phone' => $this->faker->phoneNumber,
            'title' => $this->faker->jobTitle,
            'created_by_user_id' => CrmUser::factory(),
        ];
    }
}