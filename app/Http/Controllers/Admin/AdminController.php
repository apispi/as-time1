<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UsageCounter;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function overview(UsageCounter $usage): View
    {
        $daily = $usage->daily(14);

        return view('admin.overview', [
            'stats' => [
                'users' => User::count(),
                'verified' => User::whereNotNull('email_verified_at')->count(),
                'new_7d' => User::where('created_at', '>=', now()->subDays(7))->count(),
                'admins' => User::where('is_admin', true)->count(),
            ],
            'methods' => [
                'Google' => User::whereNotNull('google_id')->count(),
                'ApiSpi' => User::whereNotNull('apispi_id')->count(),
                'Password' => User::whereNotNull('password')->count(),
            ],
            'daily' => $daily,
            'today' => end($daily),
            'recent' => User::latest()->limit(5)->get(),
        ]);
    }

    public function users(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        $users = User::query()
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('email', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")))
            ->when($request->query('filter') === 'unverified', fn ($q) => $q->whereNull('email_verified_at'))
            ->when($request->query('filter') === 'admins', fn ($q) => $q->where('is_admin', true))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.users', ['users' => $users, 'search' => $search, 'filter' => $request->query('filter')]);
    }

    public function toggleAdmin(Request $request, User $user): RedirectResponse
    {
        // Demoting yourself could leave nobody able to get back in here.
        if ($user->is($request->user())) {
            return back()->withErrors(['admin' => 'You cannot change your own admin access.']);
        }

        $user->forceFill(['is_admin' => ! $user->is_admin])->save();

        return back()->with('status', $user->is_admin ? "{$user->email} is now an admin." : "{$user->email} is no longer an admin.");
    }

    public function verify(User $user): RedirectResponse
    {
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return back()->with('status', "{$user->email} is marked as verified.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['admin' => 'You cannot delete your own account here.']);
        }

        DB::transaction(function () use ($user) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $user->delete();
        });

        return back()->with('status', "{$user->email} was deleted.");
    }

    public function system(): View
    {
        $commit = rescue(fn () => trim((string) @shell_exec('git -C '.escapeshellarg(base_path()).' log -1 --format="%h %s (%cr)" 2>/dev/null')), '', false);

        $checks = [
            ['Debug mode off', ! config('app.debug'), 'APP_DEBUG=true exposes stack traces to the public.'],
            ['APP_URL matches this site', rtrim((string) config('app.url'), '/') === rtrim(request()->root(), '/'), 'APP_URL is '.config('app.url').'; links in emails use it.'],
            ['Email can be delivered', ! in_array(config('mail.default'), ['log', 'array'], true), 'MAIL_MAILER='.config('mail.default').' — verification emails are written to the log, not sent.'],
            ['Google sign-in configured', SocialAuthController::configured('google'), 'Set GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET.'],
            ['ApiSpi sign-in configured', SocialAuthController::configured('apispi'), 'Set APISPI_CLIENT_ID and APISPI_CLIENT_SECRET.'],
        ];

        return view('admin.system', [
            'checks' => $checks,
            'facts' => [
                'Environment' => app()->environment(),
                'Commit' => $commit ?: 'unknown',
                'Laravel' => Application::VERSION,
                'PHP' => PHP_VERSION,
                'Database' => config('database.default'),
                'Cache / sessions' => config('cache.default').' / '.config('session.driver'),
                'Mailer' => config('mail.default'),
                'Timezone database' => class_exists(\IntlTimeZone::class) ? \IntlTimeZone::getTZDataVersion() : 'PHP built-in',
            ],
        ]);
    }
}
