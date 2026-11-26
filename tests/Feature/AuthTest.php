<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
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

        config([
            'services.google.client_id' => 'id', 'services.google.client_secret' => 'secret',
            'services.apispi.client_id' => 'aid', 'services.apispi.client_secret' => 'asecret',
        ]);
    }

    private function googleReturns(string $id, string $email, bool $verified = true, string $name = 'Ada Lovelace'): void
    {
        $user = (new GoogleUser)->setRaw(['email_verified' => $verified])->map([
            'id' => $id, 'name' => $name, 'email' => $email,
        ]);

        Socialite::shouldReceive('driver->user')->andReturn($user);
    }

    private function apispiReturns(string $sub, string $email, bool $verified = true): void
    {
        $user = (new GoogleUser)->setRaw(['sub' => $sub, 'email_verified' => $verified])->map([
            'id' => $sub, 'name' => 'Ada Lovelace', 'email' => $email,
        ]);

        Socialite::shouldReceive('driver->user')->andReturn($user);
    }

    public function test_pages_render(): void
    {
        $this->get('/login')->assertOk()->assertSee('Continue with Google')->assertSee('Continue with ApiSpi');
        $this->get('/register')->assertOk();
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_google_button_is_hidden_until_configured(): void
    {
        config(['services.google.client_id' => null]);

        $this->get('/login')->assertOk()->assertDontSee('Continue with Google')->assertSee('Continue with ApiSpi');
        $this->get('/auth/google/redirect')->assertRedirect('/login')->assertSessionHasErrors('social');
    }

    public function test_register_sends_a_verification_email_and_gates_the_dashboard(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'Ada', 'email' => 'ada@example.com',
            'password' => 'correct-horse', 'password_confirmation' => 'correct-horse',
        ])->assertRedirect('/email/verify');

        $user = User::where('email', 'ada@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->email_verified_at);
        $this->assertTrue(Hash::check('correct-horse', $user->password));
        Notification::assertSentTo($user, VerifyEmail::class);

        $this->get('/dashboard')->assertRedirect('/email/verify');
        $this->get('/email/verify')->assertOk()->assertSee('ada@example.com');

        $link = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1($user->email)]);
        $this->get($link)->assertRedirect('/dashboard');

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->get('/dashboard')->assertOk()->assertSee('ada@example.com')->assertSee('Password');
    }

    public function test_a_tampered_or_foreign_verification_link_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();
        $other = User::factory()->unverified()->create();

        $this->actingAs($user);
        $this->get(URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $other->id, 'hash' => sha1($other->email)]))->assertForbidden();
        $this->get(route('verification.verify', ['id' => $user->id, 'hash' => sha1($user->email)]))->assertForbidden();

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_verification_email_can_be_resent(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->post('/email/verification-notification')->assertSessionHas('status');

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_registering_over_a_pending_signup_replaces_it(): void
    {
        Notification::fake();
        User::factory()->unverified()->create(['email' => 'ada@example.com', 'password' => 'squatter-pw']);

        $this->post('/register', ['name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'correct-horse', 'password_confirmation' => 'correct-horse'])
            ->assertRedirect('/email/verify');

        $this->assertSame(1, User::count());
        $this->assertTrue(Hash::check('correct-horse', User::first()->password));
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

        $this->post('/login', ['email' => $user->email, 'password' => 'correct-horse'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);

        $this->get('/login')->assertRedirect('/dashboard');

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

        $this->get('/auth/google/callback')->assertRedirect('/dashboard');

        $user = User::where('email', 'ada@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('g-1', $user->google_id);
        $this->assertNull($user->password);
        $this->assertNotNull($user->email_verified_at);
        $this->get('/dashboard')->assertSee('Google');
    }

    public function test_google_signs_in_a_returning_user_even_if_their_email_changed(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com']);
        $user->forceFill(['google_id' => 'g-1'])->save();
        $this->googleReturns('g-1', 'new@example.com');

        $this->get('/auth/google/callback')->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::count());
    }

    public function test_google_links_to_a_verified_password_account_and_keeps_the_password(): void
    {
        $user = User::factory()->create(['email' => 'ada@example.com', 'password' => 'correct-horse']);
        $this->googleReturns('g-1', 'ada@example.com');

        $this->get('/auth/google/callback')->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
        $this->assertSame('g-1', $user->fresh()->google_id);
        $this->assertTrue(Hash::check('correct-horse', $user->fresh()->password));
    }

    public function test_google_linking_drops_a_password_set_before_the_email_was_verified(): void
    {
        // Someone else may have registered this address first with their own password.
        $user = User::factory()->unverified()->create(['email' => 'ada@example.com', 'password' => 'attacker-pw']);
        $this->googleReturns('g-1', 'ada@example.com');

        $this->get('/auth/google/callback')->assertRedirect('/dashboard');

        $this->assertNull($user->fresh()->password);
        $this->post('/logout');
        $this->post('/login', ['email' => 'ada@example.com', 'password' => 'attacker-pw'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_google_with_an_unverified_email_is_refused(): void
    {
        User::factory()->create(['email' => 'ada@example.com']);
        $this->googleReturns('g-1', 'ada@example.com', verified: false);

        $this->get('/auth/google/callback')->assertRedirect('/login')->assertSessionHasErrors('social');

        $this->assertGuest();
        $this->assertNull(User::first()->google_id);
    }

    public function test_a_failed_google_handshake_returns_to_the_login_page(): void
    {
        Socialite::shouldReceive('driver->user')->andThrow(new InvalidStateException);

        $this->get('/auth/google/callback')->assertRedirect('/login')->assertSessionHasErrors('social');
        $this->assertGuest();
    }

    public function test_google_redirect_goes_to_google_with_this_sites_callback(): void
    {
        $location = $this->get('/auth/google/redirect')->assertRedirect()->headers->get('Location');

        $this->assertStringStartsWith('https://accounts.google.com/', $location);
        $this->assertStringContainsString(urlencode(url('/auth/google/callback')), $location);
    }

    public function test_apispi_creates_an_account_and_links_by_email(): void
    {
        $existing = User::factory()->create(['email' => 'ada@example.com', 'password' => 'correct-horse']);
        $this->apispiReturns('42', 'ada@example.com');

        $this->get('/auth/apispi/callback')->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($existing);
        $this->assertSame('42', $existing->fresh()->apispi_id);
        $this->get('/dashboard')->assertSee('ApiSpi + Password');
    }

    public function test_apispi_with_an_unverified_email_is_refused(): void
    {
        $this->apispiReturns('42', 'ada@example.com', verified: false);

        $this->get('/auth/apispi/callback')->assertRedirect('/login')->assertSessionHasErrors('social');
        $this->assertGuest();
        $this->assertSame(0, User::count());
    }

    public function test_apispi_redirect_uses_pkce_and_openid_scopes(): void
    {
        $location = $this->get('/auth/apispi/redirect')->assertRedirect()->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $q);

        $this->assertStringStartsWith('https://apispi.com/oauth/authorize?', $location);
        $this->assertSame('aid', $q['client_id']);
        $this->assertSame('openid profile email', $q['scope']);
        $this->assertSame('S256', $q['code_challenge_method']);
        $this->assertNotEmpty($q['code_challenge']);
        $this->assertSame(url('/auth/apispi/callback'), $q['redirect_uri']);
    }

    public function test_unknown_provider_is_a_404(): void
    {
        $this->get('/auth/github/redirect')->assertNotFound();
    }
}
