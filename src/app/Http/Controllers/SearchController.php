<?php

namespace LaravelEnso\Searchable\app\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use LaravelEnso\Searchable\app\Classes\Finder;

class SearchController extends Controller
{
    public function __invoke(Request $request)
    {
        return (new Finder($request->get('query')))
            ->search();
    }
}
