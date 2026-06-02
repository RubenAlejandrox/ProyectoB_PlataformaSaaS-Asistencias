<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

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
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        $this->app->booted(function (): void {
            if ($this->app->runningInConsole()) {
                return;
            }

            $request = request();

            if ($request->isSecure()) {
                config(['session.secure' => true]);
            }

            // SESSION_DOMAIN distinto al host real impide que el navegador envíe la cookie (loop /login).
            $domain = config('session.domain');
            if (is_string($domain) && $domain !== '' && $request->getHost() !== $domain) {
                config(['session.domain' => null]);
            }
        });
    }
}
