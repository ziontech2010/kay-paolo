<?php

namespace App\Providers;

use App\Mail\Transport\ZeptoMailTransport;
use Illuminate\Support\Facades\Mail;
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
        if ($this->app->environment('production') || str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        Mail::extend('zeptomail', function (array $config = []) {
            $services = config('services.zeptomail', []);

            return new ZeptoMailTransport(
                token: (string) ($services['token'] ?? ''),
                host: (string) ($services['host'] ?? 'api.zeptomail.com'),
                bounceAddress: $services['bounce_address'] ?: null,
            );
        });
    }
}
