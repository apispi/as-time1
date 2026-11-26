<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        // One message for "no such account" and "wrong password", so the form
        // does not reveal which addresses are registered. An account created
        // through Google has no password and fails here the same way.
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'These credentials do not match our records.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            // Only a verified account owns its address. An unverified one is a
            // pending sign-up that anyone could have started, so it must not
            // lock the real owner out of registering.
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique('users', 'email')->whereNotNull('email_verified_at')],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        // Re-registering over a pending sign-up replaces its name and password;
        // the account stays unusable until the link in the inbox is opened.
        $user = User::firstOrNew(['email' => $validated['email']]);
        $user->fill(['name' => $validated['name'], 'password' => $validated['password']])->save();

        // Sends the verification email (MustVerifyEmail on the User model).
        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('verification.notice');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
