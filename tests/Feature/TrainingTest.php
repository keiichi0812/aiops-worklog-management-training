<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\License;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TrainingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_read_business_data(): void
    {
        $this->get(route(config('training.topic') === 'license' ? 'licenses.index' : 'worklogs.index'))->assertRedirect(route('login'));
    }

    public function test_self_registration_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [])->assertNotFound();
    }

    public function test_general_users_can_read_masters_but_cannot_manage_them_or_view_accounts(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();

        $this->actingAs($user)->getJson('/masters/customers')->assertJsonPath('records.data.0.id', $customer->id);
        $this->post('/masters/customers', ['code' => 'NEW', 'name' => '不正な追加'])->assertForbidden();
        $this->patch('/masters/customers/'.$customer->id, ['name' => '不正な更新'])->assertForbidden();
        $this->delete('/masters/customers/'.$customer->id)->assertForbidden();
        $this->getJson('/masters/accounts')->assertForbidden();
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => $customer->name]);
        $this->assertDatabaseMissing('customers', ['code' => 'NEW']);
    }

    public function test_admin_can_create_account_with_hashed_password_and_rejects_duplicate_email(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $payload = ['name' => '新しい受講者', 'email' => 'new@example.test', 'password' => 'Training!2026', 'role' => 'user', 'hourly_rate' => 3200];

        $this->actingAs($admin)->post('/masters/accounts', $payload)->assertRedirect('/masters/accounts');

        $created = User::where('email', 'new@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('Training!2026', $created->password));
        $this->assertSame('user', $created->role);
        $this->assertSame(3200, $created->hourly_rate);
        $this->assertNotNull($created->email_verified_at);
        $this->post('/masters/accounts', $payload)->assertSessionHasErrors('email');
        $this->assertSame(1, User::where('email', 'new@example.test')->count());
    }

    public function test_general_profile_update_cannot_escalate_role_or_change_rate(): void
    {
        $user = User::factory()->create(['role' => 'user', 'hourly_rate' => 3000]);

        $this->actingAs($user)->patch('/settings/profile', ['name' => '更新した名前', 'email' => $user->email, 'role' => 'admin', 'hourly_rate' => 9000])->assertRedirect('/settings/profile');

        $user->refresh();
        $this->assertSame('更新した名前', $user->name);
        $this->assertSame('user', $user->role);
        $this->assertSame(3000, $user->hourly_rate);
    }

    public function test_master_codes_are_unique_and_related_masters_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::factory()->create();
        if (config('training.topic') === 'license') {
            License::factory()->create(['customer_id' => $customer->id]);
        } else {
            Project::factory()->create(['customer_id' => $customer->id]);
        }

        $this->actingAs($admin)->post('/masters/customers', ['code' => $customer->code, 'name' => '重複'])->assertSessionHasErrors('code');
        $this->delete('/masters/customers/'.$customer->id)->assertSessionHasErrors('resource');
        $this->assertModelExists($customer);
    }

    public function test_seed_can_run_twice_without_duplicating_training_records(): void
    {
        $this->seed();
        $this->seed();

        $this->assertDatabaseCount('users', 3);
        $this->assertSame('admin', User::where('email', 'admin@example.test')->firstOrFail()->role);
        $this->assertSame(4000, User::where('email', 'user-b@example.test')->firstOrFail()->hourly_rate);
        $this->assertDatabaseCount(config('training.topic') === 'license' ? 'licenses' : 'work_logs', config('training.topic') === 'license' ? 7 : 6);
    }
}
