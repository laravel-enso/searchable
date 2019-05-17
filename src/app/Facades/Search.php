<?php

namespace LaravelEnso\Searchable\app\Facades;

use Illuminate\Support\Facades\Facade;

class Search extends Facade
{
    public static function getFacadeAccessor()
    {
        return 'search';
    }
}
