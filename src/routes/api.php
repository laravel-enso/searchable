<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'core'])
    ->prefix('api/core/search')->as('core.search.')
    ->namespace('LaravelEnso\Searchable\App\Http\Controllers')
    ->group(fn () => Route::get('index', 'Search')->name('index'));
