<?php

namespace LaravelEnso\Searchable\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use LaravelEnso\Searchable\Services\Finder;

class Search extends Controller
{
    public function __invoke(Request $request)
    {
        return (new Finder($request->get('query')))->search();
    }
}
