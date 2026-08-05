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
        $configuredUrl = (string) config('app.url');

        if ($this->app->runningInConsole()) {
            if (str_starts_with($configuredUrl, 'https://')) {
                URL::forceScheme('https');
            }

            return;
        }

        $configuredHost = parse_url($configuredUrl, PHP_URL_HOST);
        $request = request();

        // Fresh clones often keep a production APP_URL. Generate CSS/JS from the
        // host actually being browsed so styles still load locally.
        if (is_string($configuredHost) && strcasecmp($configuredHost, $request->getHost()) !== 0) {
            URL::forceRootUrl($request->root());
            URL::forceScheme($request->getScheme());

            return;
        }

        if (str_starts_with($configuredUrl, 'https://')) {
            URL::forceScheme('https');
        }
    }
}
