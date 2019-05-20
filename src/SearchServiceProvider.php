<?php

namespace LaravelEnso\Searchable;

use Illuminate\Support\ServiceProvider;
use LaravelEnso\Searchable\app\Facades\Search;

class SearchServiceProvider extends ServiceProvider
{
    public $register = [];

    public function boot()
    {
        Search::register($this->register);
    }
}
