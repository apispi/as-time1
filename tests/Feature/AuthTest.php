<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as GoogleUser;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.google.client_id' => 'id', 'services.google.client_secret' => 'secret']);
    }

    private function googleReturns(string $id, string $email, bool $verified = true, string $name = 'Ada Lovelace'): void
    {
        $user = (new GoogleUser)->setRaw(['email_verified' => $verified])->map([
            'id' => $id, 'name' => $name, 'email' => $email,
        ]);

        Socialite::shouldReceive('driver->user')->andReturn($user);
    }

    public function test_pages_render(): void
    {
        $this->get('/login')->assertOk()->assertSee('Continue with Google');
        $this->get('/register')->assertOk();
        $this->get('/account')->assertRedirect('/login');
    }

    public function test_google_button_is_hidden_until_configured(): void
    {
        config(['services.google.client_id' => null]);

        $this->get('/login')->assertOk()->assertDontSee('Continue with Google');
        $this->get('/auth/google/redirect')->assertRedirect('/login')->assertSessionHasErrors('google');
    }

    public function test_register_creates_an_account_and_signs_in(): void
    {
        $this->post('/register', [
            'name' => 'Ada', 'email' => 'ada@example.com',
            'password' => 'correct-horse', 'password_confirmation' => 'correct-horse',
        ])->assertRedirect('/account');

        $this->assertAuthenticated();
        $user = User::where('email', 'ada@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('correct-horse', $user->password));
        $this->get('/account')->assertOk()->assertSee('ada@example.com')->assertSee('Password');
    }

    public function test_register_rejects_duplicates_and_weak_or_mismatched_passwords(): void
    {
        User::factory()->create(['email' => 'ada@example.com']);

        $this->post('/register', ['name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'correct-horse', 'password_confirmation' => 'correct-horse'])
            ->assertSessionHasErrors('email');
        $this->post('/register', ['name' => 'Bob', 'email' => 'bob@example.com', 'password' => 'short', 'password_confirmation' => 'short'])
            ->assertSessionHasErrors('password');
        $this->post('/register', ['name' => 'Bob', 'email' => 'bob@example.com', 'password' => 'correct-horse', 'password_confirmation' => 'different'])
            ->assertSessionHasErrors('password');

        $this->assertGuest();
        $this->assertSame(1, User::count());
    }

    public function test_login_and_logout(): void
    {
        $user = User::factory()->create(['password' => 'correct-horse']);

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/login', ['email' => $user->email, 'password' => 'correct-horse'])->assertRedirect('/account');
        $this->assertAuthenticatedAs($user);

        $this->get('/login')->assertRedirect('/account');

        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_login_is_throttled(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post('/login', ['email' => 'a@example.com', 'password' => 'x'])->assertStatus(302);
        }

        $this->post('/login', ['email' => 'a@example.com', 'password' => 'x'])->assertStatus(429);
    }

    public function test_google_creates_a_new_account(): void
    {
        $this->googleReturns('g-1', 'Ada@Example.com');

        $this->get('/auth/google/callback')->assertRedirect('/account');

        $user = User::where('email', 'ada@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('g-1', $user->google_id);
        $this->assertNull($user->password);
        $this->assertNotNull($user->email_verified_at);
        $this->get('/account')->assertSee('Google');
    }

    public function test_google_signs_in_a_returning_user_even_if_their_email_changed(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com']);
        $user->forceFill(['google_id' => 'g-1'])->save();
        $this->googleReturns('g-1', 'new@example.com');

        $this->get('/auth/google/callback')->assertRedirect('/account');

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::count());
    }

    public function test_google_links_to_a_verified_password_account_and_keeps_the_password(): void
    {
        $user = User::factory()->create(['email' => 'ada@example.com', 'password' => 'correct-horse']);
        $this->googleReturns('g-1', 'ada@example.com');

        $this->get('/auth/google/callback')->assertRedirect('/account');

        $this->assertAuthenticatedAs($user);
        $this->assertSame('g-1', $user->fresh()->google_id);
        $this->assertTrue(Hash::check('correct-horse', $user->fresh()->password));
    }

    public function test_google_linking_drops_a_password_set_before_the_email_was_verified(): void
    {
        // Someone else may have registered this address first with their own password.
        $user = User::factory()->unverified()->create(['email' => 'ada@example.com', 'password' => 'attacker-pw']);
        $this->googleReturns('g-1', 'ada@example.com');

        $this->get('/auth/google/callback')->assertRedirect('/account');

        $this->assertNull($user->fresh()->password);
        $this->post('/logout');
        $this->post('/login', ['email' => 'ada@example.com', 'password' => 'attacker-pw'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_google_with_an_unverified_email_is_refused(): void
    {
        User::factory()->create(['email' => 'ada@example.com']);
        $this->googleReturns('g-1', 'ada@example.com', verified: false);

        $this->get('/auth/google/callback')->assertRedirect('/login')->assertSessionHasErrors('google');

        $this->assertGuest();
        $this->assertNull(User::first()->google_id);
    }

    public function test_a_failed_google_handshake_returns_to_the_login_page(): void
    {
        Socialite::shouldReceive('driver->user')->andThrow(new InvalidStateException);

        $this->get('/auth/google/callback')->assertRedirect('/login')->assertSessionHasErrors('google');
        $this->assertGuest();
    }

    public function test_google_redirect_goes_to_google_with_this_sites_callback(): void
    {
        $location = $this->get('/auth/google/redirect')->assertRedirect()->headers->get('Location');

        $this->assertStringStartsWith('https://accounts.google.com/', $location);
        $this->assertStringContainsString(urlencode(url('/auth/google/callback')), $location);
    }
}
