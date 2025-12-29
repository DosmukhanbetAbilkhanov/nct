<?php

namespace App\Providers;

use App\Services\Contracts\SmsServiceInterface;
use App\Services\MobizonSmsService;
use App\Services\NationalCatalogService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(NationalCatalogService::class, function () {
            return NationalCatalogService::fromConfig();
        });

        $this->app->bind(SmsServiceInterface::class, MobizonSmsService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // National Catalog API rate limiter - 30 requests per minute (conservative)
        // This ensures we don't exceed the API's rate limit
        RateLimiter::for('national-catalog-api', function (object $job) {
            return Limit::perMinute(30)
                ->by('national-catalog')
                ->response(function ($request, array $headers) {
                    return 'Too many requests. Please try again later.';
                });
        });
    }
}
