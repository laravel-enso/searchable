<?php

Route::middleware(['web', 'auth', 'core'])
    ->prefix('api/core/search')->as('core.search.')
    ->namespace('LaravelEnso\Searchable\app\Http\Controllers')
    ->group(function () {
        Route::get('index', 'Search')->name('index');
    });
