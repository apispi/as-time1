<?php

namespace App\Providers;

use App\Socialite\ApiSpiProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Contracts\Factory as Socialite;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // The public REST API needs no key, so the limit is per IP address.
        $this->app->make(Socialite::class)->extend('apispi', fn ($app) => $app->make(Socialite::class)
            ->buildProvider(ApiSpiProvider::class, config('services.apispi')));

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));
    }
}
