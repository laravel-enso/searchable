<?php

namespace LaravelEnso\Searchable\app\Services;

class Search
{
    private $sources;

    public function __construct()
    {
        $this->sources = collect();
    }

    public function register($sources)
    {
        $this->sources = $this->sources->merge($sources);
    }

    public function all()
    {
        return $this->sources;
    }

    public function remove($models)
    {
        $models = collect($models);

        $this->sources = $this->sources
            ->reject(function ($config, $model) use ($models) {
                return $models->contains($model);
            });
    }
}
