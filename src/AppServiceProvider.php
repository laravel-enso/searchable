<?php

namespace LaravelEnso\Searchable;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        $this->loadRoutesFrom(__DIR__.'/routes/api.php');

        $this->mergeConfigFrom(__DIR__.'/config/searchable.php', 'enso.searchable');

        $this->publishes([
            __DIR__.'/config' => config_path('enso'),
        ], 'searchable-config');

        $this->publishes([
            __DIR__.'/config' => config_path('enso'),
        ], 'enso-config');
    }

    public function register()
    {
        //
    }
}
