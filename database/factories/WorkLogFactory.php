<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use App\Models\WorkLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WorkLog> */
class WorkLogFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['project_id' => Project::factory(), 'user_id' => User::factory(), 'worked_on' => today(), 'half_hours' => 4, 'description' => '研修用の作業'];
    }
}
