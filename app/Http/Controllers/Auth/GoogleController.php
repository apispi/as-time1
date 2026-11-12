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

class GoogleController extends Controller
{
    public function redirect(): SymfonyRedirect
    {
        if (! self::configured()) {
            return $this->failed('Google sign-in is not configured yet.');
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        if (! self::configured()) {
            return $this->failed('Google sign-in is not configured yet.');
        }

        try {
            $google = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            // Cancelled consent, an expired or replayed state, or a rejected
            // code all land here; none of them is worth a 500.
            report($e);

            return $this->failed('Google sign-in did not complete. Please try again.');
        }

        $email = strtolower((string) $google->getEmail());
        $raw = $google->user ?? [];
        $verified = (bool) ($raw['email_verified'] ?? $raw['verified_email'] ?? false);

        // The email address is what joins a Google identity to an account
        // here, so one Google has not verified cannot be trusted to do that.
        if ($email === '' || ! $verified) {
            return $this->failed('Your Google account has no verified email address.');
        }

        $user = User::where('google_id', $google->getId())->first()
            ?? User::where('email', $email)->first();

        if ($user === null) {
            $user = new User(['name' => $google->getName() ?: $email, 'email' => $email]);
        }

        if ($user->google_id === null) {
            // Registration here does not confirm email ownership, so a
            // password on a never-verified account may have been set by
            // someone other than the owner of this address. Google has just
            // proven ownership; drop that password rather than leave a second
            // way into the account.
            if ($user->exists && $user->email_verified_at === null) {
                $user->password = null;
            }
        }

        $user->forceFill([
            'google_id' => $google->getId(),
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('account'));
    }

    public static function configured(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }

    private function failed(string $message): RedirectResponse
    {
        return redirect()->route('login')->withErrors(['google' => $message]);
    }
}
