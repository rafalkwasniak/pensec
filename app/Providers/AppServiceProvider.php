<?php

namespace App\Providers;

use App\Http\Middleware\AuthenticateDevice;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('reports', function (Request $request): Limit {
            $device = $request->attributes->get(AuthenticateDevice::DEVICE_ATTRIBUTE);

            return Limit::perMinute(config('pensec.reports.rate_limit_per_minute'))
                ->by($device?->getKey() ?? $request->ip());
        });
    }
}
