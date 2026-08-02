<?php

namespace Keepcloud\Directions;

use Illuminate\Support\ServiceProvider;

class DirectionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Directions::class, fn () => new Directions);
    }

    public function boot(): void
    {
        //
    }
}
