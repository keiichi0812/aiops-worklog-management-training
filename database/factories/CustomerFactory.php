<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Customer> */
class CustomerFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['code' => fake()->unique()->bothify('C-####??'), 'name' => fake()->company()];
    }
}
