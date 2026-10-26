<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Project> */
class ProjectFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['code' => fake()->unique()->bothify('PR-####??'), 'name' => fake()->sentence(2), 'customer_id' => Customer::factory(), 'division' => '東京'];
    }
}
