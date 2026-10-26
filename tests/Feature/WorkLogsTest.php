<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Models\WorkLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WorkLogsTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_report_limits_owner_and_year_month_and_calculates_known_costs(): void
    {
        $user = User::factory()->create(['hourly_rate' => 3000]);
        $other = User::factory()->create(['hourly_rate' => 4000]);
        $project = Project::factory()->create();
        WorkLog::factory()->create(['user_id' => $user->id, 'project_id' => $project->id, 'worked_on' => '2026-10-31', 'half_hours' => 4]);
        WorkLog::factory()->create(['user_id' => $other->id, 'project_id' => $project->id, 'worked_on' => '2026-10-31', 'half_hours' => 3]);
        WorkLog::factory()->create(['user_id' => $user->id, 'worked_on' => '2026-11-01']);
        WorkLog::factory()->create(['user_id' => $user->id, 'worked_on' => '2025-10-31']);

        $response = $this->actingAs($user)->getJson('/worklogs?month=2026-10');

        $response->assertJsonCount(1, 'records.data')->assertJsonPath('records.data.0.cost', 6000)->assertJsonCount(1, 'summary')->assertJsonPath('summary.0.cost', 6000);
        $this->assertSame(2, $response->json('summary.0.hours'));
        $this->assertSame([$user->id], collect($response->json('options.user_id'))->pluck('id')->all());
        $response->assertDontSee($other->name);
    }

    public function test_admin_report_includes_all_owners_without_mixing_rates(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $a = User::factory()->create(['hourly_rate' => 3000]);
        $b = User::factory()->create(['hourly_rate' => 4000]);
        $project = Project::factory()->create();
        WorkLog::factory()->create(['project_id' => $project->id, 'user_id' => $a->id, 'worked_on' => '2026-10-20', 'half_hours' => 4]);
        WorkLog::factory()->create(['project_id' => $project->id, 'user_id' => $b->id, 'worked_on' => '2026-10-20', 'half_hours' => 3]);

        $response = $this->actingAs($admin)->getJson('/worklogs?month=2026-10');

        $response->assertJsonCount(2, 'records.data');
        $this->assertSame(12000, collect($response->json('summary'))->sum('cost'));
        $this->assertSame(3.5, collect($response->json('summary'))->sum('hours'));
    }

    public function test_other_user_cannot_read_update_or_delete_worklog(): void
    {
        $owner = User::factory()->create();
        $log = WorkLog::factory()->create(['user_id' => $owner->id]);
        $other = User::factory()->create();

        $this->actingAs($other)->getJson('/worklogs/'.$log->id)->assertNotFound();
        $this->patch('/worklogs/'.$log->id, [])->assertNotFound();
        $this->delete('/worklogs/'.$log->id)->assertNotFound();
        $this->assertModelExists($log);
    }

    public function test_create_ignores_forged_worker_and_admin_update_preserves_original_worker(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $project = Project::factory()->create();
        $payload = ['project_id' => $project->id, 'worked_on' => '2026-10-20', 'hours' => 0.5, 'description' => '作業', 'user_id' => $other->id];

        $this->actingAs($user)->post('/worklogs', $payload)->assertRedirect('/worklogs');

        $log = WorkLog::firstOrFail();
        $this->assertDatabaseHas('work_logs', ['id' => $log->id, 'user_id' => $user->id, 'half_hours' => 1]);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->patch('/worklogs/'.$log->id, [...$payload, 'hours' => 1.5])->assertRedirect('/worklogs');
        $this->assertDatabaseHas('work_logs', ['id' => $log->id, 'user_id' => $user->id, 'half_hours' => 3]);
        $this->delete('/worklogs/'.$log->id)->assertRedirect('/worklogs');
        $this->assertModelMissing($log);
    }

    #[DataProvider('invalidHours')]
    public function test_invalid_hours_are_rejected(mixed $hours): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/worklogs', ['project_id' => Project::factory()->create()->id, 'worked_on' => '2026-10-20', 'hours' => $hours])->assertSessionHasErrors('hours');

        $this->assertDatabaseCount('work_logs', 0);
    }

    /** @return array<string, array{mixed}> */
    public static function invalidHours(): array
    {
        return ['zero' => [0], 'negative' => [-1], 'quarter_hour' => [0.25], 'non_numeric' => ['abc']];
    }

    public function test_multiple_logs_on_same_day_and_project_are_allowed(): void
    {
        $user = User::factory()->create();
        $payload = ['project_id' => Project::factory()->create()->id, 'worked_on' => '2026-10-20', 'hours' => 0.5];

        $this->actingAs($user)->post('/worklogs', $payload)->assertRedirect('/worklogs');
        $this->post('/worklogs', $payload)->assertRedirect('/worklogs');

        $this->assertDatabaseCount('work_logs', 2);
    }

    public function test_rate_cannot_change_after_work_is_recorded(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['hourly_rate' => 3000]);
        WorkLog::factory()->create(['user_id' => $user->id]);

        $this->actingAs($admin)->patch('/masters/accounts/'.$user->id, ['name' => $user->name, 'email' => $user->email, 'role' => 'user', 'hourly_rate' => 4000])->assertSessionHasErrors('hourly_rate');

        $this->assertSame(3000, $user->fresh()->hourly_rate);
    }
}
