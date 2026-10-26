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

    public function test_monthly_report_limits_owner_and_year_month_and_aggregates_known_hours(): void
    {
        $user = User::factory()->create([]);
        $other = User::factory()->create([]);
        $project = Project::factory()->create();
        WorkLog::factory()->create(['user_id' => $user->id, 'project_id' => $project->id, 'worked_on' => '2026-10-31', 'half_hours' => 4]);
        WorkLog::factory()->create(['user_id' => $other->id, 'project_id' => $project->id, 'worked_on' => '2026-10-31', 'half_hours' => 3]);
        WorkLog::factory()->create(['user_id' => $user->id, 'worked_on' => '2026-11-01']);
        WorkLog::factory()->create(['user_id' => $user->id, 'worked_on' => '2025-10-31']);

        $response = $this->actingAs($user)->getJson('/worklogs?month=2026-10');

        $response->assertJsonCount(1, 'records.data')->assertJsonCount(1, 'summary')->assertJsonPath('totalHours', 2)->assertJsonPath('dailySummary.0.hours', 2);
        $this->assertSame(2, $response->json('summary.0.hours'));
        $this->assertSame([$user->id], collect($response->json('options.user_id'))->pluck('id')->all());
        $response->assertDontSee($other->name);
    }

    public function test_admin_report_includes_all_owners_with_separate_daily_totals(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $a = User::factory()->create([]);
        $b = User::factory()->create([]);
        $project = Project::factory()->create();
        WorkLog::factory()->create(['project_id' => $project->id, 'user_id' => $a->id, 'worked_on' => '2026-10-20', 'half_hours' => 4]);
        WorkLog::factory()->create(['project_id' => $project->id, 'user_id' => $b->id, 'worked_on' => '2026-10-20', 'half_hours' => 3]);

        $response = $this->actingAs($admin)->getJson('/worklogs?month=2026-10');

        $response->assertJsonCount(2, 'records.data');
        $response->assertJsonCount(2, 'dailySummary')->assertJsonPath('totalHours', 3.5);
        $this->assertSame([1.5, 2], collect($response->json('dailySummary'))->pluck('hours')->sort()->values()->all());
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

    public function test_daily_totals_combine_projects_but_keep_days_and_owners_separate(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        foreach ([['2026-10-20', 4], ['2026-10-20', 3], ['2026-10-21', 1]] as [$date, $halfHours]) {
            WorkLog::factory()->create(['user_id' => $user->id, 'worked_on' => $date, 'half_hours' => $halfHours]);
        }
        WorkLog::factory()->create(['user_id' => $other->id, 'worked_on' => '2026-10-20', 'half_hours' => 18]);

        $response = $this->actingAs($user)->getJson('/worklogs?month=2026-10');

        $response->assertJsonPath('totalHours', 4)->assertJsonCount(2, 'dailySummary')
            ->assertJsonPath('dailySummary.0.date', '2026-10-21')->assertJsonPath('dailySummary.0.hours', 0.5)
            ->assertJsonPath('dailySummary.1.hours', 3.5);
        $this->assertArrayNotHasKey('ownRate', $response->json());
        foreach ($response->json('records.data') as $row) {
            $this->assertArrayNotHasKey('cost', $row);
        }
    }
}
