<?php

namespace App\Providers;

use App\Kafka\Handlers\RouteStopCheckedInHandler;
use Illuminate\Support\ServiceProvider;
use Junges\Kafka\Facades\Kafka;

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
    }
}
