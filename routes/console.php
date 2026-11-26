<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Grant or revoke admin access. There is no sign-up path to admin, so the
// first one is made here: ./cx.sh 33 "user:admin you@example.com"
Artisan::command('user:admin {email} {--revoke : Remove admin access instead}', function (string $email) {
    $user = User::where('email', strtolower($email))->first();

    if ($user === null) {
        $this->error("No user with email {$email}. Sign up on the site first.");

        return 1;
    }

    $user->forceFill(['is_admin' => ! $this->option('revoke')])->save();
    $this->info($user->is_admin ? "{$user->email} is now an admin." : "{$user->email} is no longer an admin.");

    return 0;
})->purpose('Grant (or --revoke) admin access for a user');
