<?php

namespace LaravelEnso\Searchable\Facades;

use Illuminate\Support\Facades\Facade;

class Search extends Facade
{
    public static function getFacadeAccessor()
    {
        return 'search';
    }
}
