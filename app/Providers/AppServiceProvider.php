<?php

namespace App\Providers;

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
        \Illuminate\Http\Middleware\TrustProxies::at(config('app.trusted_proxies', []));
        foreach ([\App\Models\Ticket::class, \App\Models\Event::class, \App\Models\Organizer::class,
            \App\Models\EventRegistrationRequest::class, \App\Models\WithdrawalRequest::class] as $model) {
            $model::observe(\App\Observers\ActivityNotificationObserver::class);
        }
    }
}
