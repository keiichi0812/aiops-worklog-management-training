<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TimeOnlyTrainingTest extends TestCase
{
    use RefreshDatabase;

    public function test_accounts_do_not_store_or_expose_monetary_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $response = $this->actingAs($admin)->getJson('/masters/accounts')->assertOk();

        $this->assertFalse(Schema::hasColumn('users', 'hourly_rate'));
        $this->assertArrayNotHasKey('hourly_rate', $response->json('records.data.0'));
        $this->assertArrayNotHasKey('hourly_rate', $response->json('columns'));
        $this->assertNotContains('hourly_rate', array_column($response->json('fields'), 'key'));
    }

    public function test_upgrade_removes_legacy_column_and_preserves_accounts_and_business_records(): void
    {
        $this->seed();
        $table = config('training.topic') === 'license' ? 'licenses' : 'work_logs';
        $beforeUsers = DB::table('users')->orderBy('id')->get()->toArray();
        $beforeRecords = DB::table($table)->orderBy('id')->get()->toArray();
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('hourly_rate')->default(3000);
        });
        $migration = require database_path('migrations/2026_10_26_160755_remove_hourly_rate_from_users_table.php');

        $migration->up();
        $migration->up();

        $this->assertFalse(Schema::hasColumn('users', 'hourly_rate'));
        $this->assertEquals($beforeUsers, DB::table('users')->orderBy('id')->get()->toArray());
        $this->assertEquals($beforeRecords, DB::table($table)->orderBy('id')->get()->toArray());
    }
}
