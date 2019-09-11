<?php

namespace LaravelEnso\Searchable;

use Illuminate\Support\ServiceProvider;
use LaravelEnso\Searchable\app\Services\Search;

class AppServiceProvider extends ServiceProvider
{
    public $singletons = [
        'search' => Search::class,
    ];

    public function boot()
    {
        $this->load()
            ->publish();
    }

    private function load()
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        $this->loadRoutesFrom(__DIR__.'/routes/api.php');

        $this->mergeConfigFrom(__DIR__.'/config/searchable.php', 'enso.searchable');

        return $this;
    }

    private function publish()
    {
        $this->publishes([
            __DIR__.'/config' => config_path('enso'),
        ], 'searchable-config');

        $this->publishes([
            __DIR__.'/config' => config_path('enso'),
        ], 'enso-config');

        $this->publishes([
            __DIR__.'/../stubs/SearchServiceProvider.stub' => app_path('Providers/SearchServiceProvider.php'),
        ], 'search-provider');
    }
}
