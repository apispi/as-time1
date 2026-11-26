<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_admin_area_is_for_admins_only(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
        $this->actingAs(User::factory()->unverified()->create(['is_admin' => true]))->get('/admin')->assertRedirect('/email/verify');

        foreach (['/admin', '/admin/users', '/admin/system'] as $page) {
            $this->actingAs($this->admin())->get($page)->assertOk();
        }
    }

    public function test_admin_link_shows_only_for_admins(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertOk()->assertDontSee('/admin"', false);
        $this->actingAs($this->admin())->get('/dashboard')->assertOk()->assertSee('/admin"', false);
    }

    public function test_overview_counts_api_and_mcp_traffic(): void
    {
        $this->getJson('/api/v1/time')->assertOk();
        $this->getJson('/api/v1/time')->assertOk();
        $this->getJson('/api/v1/time?timezone=nope')->assertStatus(422); // not counted
        $this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'], ['Accept' => 'application/json, text/event-stream'])->assertOk();

        $today = collect($this->actingAs($this->admin())->get('/admin')->assertOk()->viewData('daily'))->last();

        $this->assertSame(2, $today['api']);
        $this->assertSame(1, $today['mcp']);
    }

    public function test_users_search_and_filters(): void
    {
        User::factory()->create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);
        User::factory()->unverified()->create(['email' => 'pending@example.com']);

        $admin = $this->admin();
        $this->actingAs($admin)->get('/admin/users?q=lovelace')->assertSee('ada@example.com')->assertDontSee('pending@example.com');
        $this->actingAs($admin)->get('/admin/users?filter=unverified')->assertSee('pending@example.com')->assertDontSee('ada@example.com');
    }

    public function test_admin_can_promote_verify_and_delete_others_but_not_themselves(): void
    {
        $admin = $this->admin();
        $user = User::factory()->unverified()->create();

        $this->actingAs($admin)->post("/admin/users/{$user->id}/verify")->assertSessionHas('status');
        $this->assertNotNull($user->fresh()->email_verified_at);

        $this->actingAs($admin)->post("/admin/users/{$user->id}/admin");
        $this->assertTrue($user->fresh()->is_admin);

        $this->actingAs($admin)->post("/admin/users/{$admin->id}/admin")->assertSessionHasErrors('admin');
        $this->actingAs($admin)->delete("/admin/users/{$admin->id}")->assertSessionHasErrors('admin');
        $this->assertTrue($admin->fresh()->is_admin);

        $this->actingAs($admin)->delete("/admin/users/{$user->id}")->assertSessionHas('status');
        $this->assertNull($user->fresh());
    }

    public function test_non_admins_cannot_use_admin_actions(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create();

        $this->actingAs($user)->post("/admin/users/{$user->id}/admin")->assertForbidden();
        $this->actingAs($user)->delete("/admin/users/{$target->id}")->assertForbidden();

        $this->assertFalse($user->fresh()->is_admin);
        $this->assertNotNull($target->fresh());
    }

    public function test_system_page_flags_undeliverable_email(): void
    {
        config(['mail.default' => 'log']);

        $this->actingAs($this->admin())->get('/admin/system')->assertOk()->assertSee('verification emails are written to the log');
    }

    public function test_user_admin_command(): void
    {
        $user = User::factory()->create(['email' => 'ada@example.com']);

        $this->artisan('user:admin', ['email' => 'ADA@example.com'])->assertSuccessful();
        $this->assertTrue($user->fresh()->is_admin);

        $this->artisan('user:admin', ['email' => 'ada@example.com', '--revoke' => true])->assertSuccessful();
        $this->assertFalse($user->fresh()->is_admin);

        $this->artisan('user:admin', ['email' => 'nobody@example.com'])->assertFailed();
    }
}
