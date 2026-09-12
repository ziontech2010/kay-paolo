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
            $token = trim((string) ($services['token'] ?? ''));

            if ($token === '') {
                $token = trim((string) (getenv('ZEPTOMAIL_TOKEN') ?: env('ZEPTOMAIL_TOKEN') ?: ''));
            }

            return new ZeptoMailTransport(
                token: trim($token, " \t\n\r\0\x0B\"'"),
                host: (string) ($services['host'] ?? env('ZEPTOMAIL_HOST') ?? 'api.zeptomail.com'),
                bounceAddress: ! empty($services['bounce_address']) ? $services['bounce_address'] : null,
            );
        });
    }
}
