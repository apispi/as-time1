<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

/**
 * Sign-in through an external identity provider: Google or ApiSpi.
 *
 * Both are handled identically. A returning user is found by the provider's
 * stable ID; a first sign-in attaches to an existing account with the same
 * email address, or creates one. Either way the provider has just proven
 * ownership of the address, so the account counts as email-verified.
 */
class SocialAuthController extends Controller
{
    /** provider => [label, users column holding its subject ID] */
    public const PROVIDERS = [
        'google' => ['label' => 'Google', 'column' => 'google_id'],
        'apispi' => ['label' => 'ApiSpi', 'column' => 'apispi_id'],
    ];

    public function redirect(string $provider): SymfonyRedirect
    {
        if (! self::configured($provider)) {
            return $this->failed(self::label($provider).' sign-in is not configured yet.');
        }

        return Socialite::driver($provider)->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        $label = self::label($provider);

        if (! self::configured($provider)) {
            return $this->failed("{$label} sign-in is not configured yet.");
        }

        try {
            $identity = Socialite::driver($provider)->user();
        } catch (Throwable $e) {
            // Cancelled consent, an expired or replayed state, or a rejected
            // code all land here; none of them is worth a 500.
            report($e);

            return $this->failed("{$label} sign-in did not complete. Please try again.");
        }

        $column = self::PROVIDERS[$provider]['column'];
        $subject = (string) $identity->getId();
        $email = strtolower((string) $identity->getEmail());
        $raw = $identity->user ?? [];
        $verified = (bool) ($raw['email_verified'] ?? $raw['verified_email'] ?? false);

        // The email address is what joins an external identity to an account
        // here, so an address the provider has not verified cannot do that.
        if ($subject === '' || $email === '' || ! $verified) {
            return $this->failed("Your {$label} account has no verified email address.");
        }

        $user = User::where($column, $subject)->first()
            ?? User::where('email', $email)->first()
            ?? new User(['name' => $identity->getName() ?: $email, 'email' => $email]);

        // A password on a never-verified account may have been set by someone
        // other than the owner of this address. The provider has just proven
        // ownership; drop that password rather than leave a second way in.
        if ($user->exists && $user->{$column} === null && $user->email_verified_at === null) {
            $user->password = null;
        }

        $user->forceFill([
            $column => $subject,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public static function configured(string $provider): bool
    {
        return isset(self::PROVIDERS[$provider])
            && filled(config("services.{$provider}.client_id"))
            && filled(config("services.{$provider}.client_secret"));
    }

    /**
     * @return list<string> providers with credentials, in display order
     */
    public static function available(): array
    {
        return array_values(array_filter(array_keys(self::PROVIDERS), self::configured(...)));
    }

    public static function label(string $provider): string
    {
        return self::PROVIDERS[$provider]['label'];
    }

    private function failed(string $message): RedirectResponse
    {
        return redirect()->route('login')->withErrors(['social' => $message]);
    }
}
