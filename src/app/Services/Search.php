<?php

namespace LaravelEnso\Searchable\app\Services;

class Search
{
    private $models;

    public function __construct()
    {
        $this->models = collect();
    }

    public function register($models)
    {
        $this->models = $this->models->merge($models);
    }

    public function remove($models)
    {
        collect($models)->each(function ($model) {
            $this->models->forget($model);
        });
    }

    public function all()
    {
        return $this->models;
    }
}
