<?php

namespace LaravelEnso\Searchable;

use Illuminate\Support\ServiceProvider;
use LaravelEnso\Searchable\app\Facades\Search;
use LaravelEnso\Searchable\app\Services\Search as Service;

class SearchServiceProvider extends ServiceProvider
{
    public $singletons = [
        'search' => Service::class,
    ];

    public $register = [];

    public function boot()
    {
        Search::register($this->register);
    }
}
