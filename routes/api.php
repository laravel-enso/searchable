<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'auth', 'core'])
    ->prefix('api/core/search')->as('core.search.')
    ->namespace('LaravelEnso\Searchable\Http\Controllers')
    ->group(fn () => Route::get('index', 'Search')->name('index'));
