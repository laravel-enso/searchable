<?php

namespace LaravelEnso\Searchable\app\Classes;

class Finder
{
    private $words;
    private $models;
    private $routes;
    private $results;

    public function __construct(string $query)
    {
        $this->words = $this->words($query);
        $this->models = collect(config('enso.searchable.models'));
        $this->routes = collect(config('enso.searchable.routes'));
        $this->results = collect();
    }

    public function search()
    {
        $this->models->keys()
            ->each(function ($model) {
                $results = $this->query($model);
                if ($results->isNotEmpty()) {
                    $this->results = $this->results->merge(
                        $this->map($results, $model)
                    );
                }
            });

        return $this->results;
    }

    private function query($model)
    {
        return $model::where(function ($query) use ($model) {
            $this->words->each(function ($word) use ($query, $model) {
                collect($this->models[$model]['attributes'])
                    ->each(function ($attribute) use ($query, $model, $word) {
                        $query->orWhere($attribute, 'like', '%'.$word.'%');

                        collect($this->scopes($model))
                            ->each(function ($scope) use ($query) {
                                $query->{$scope}();
                            });
                    });
            });
        })
        ->limit($this->limit())
        ->get();
    }

    private function map($results, $model)
    {
        return $results->map(function ($result) use ($model) {
            return [
                'id' => $result->getKey(),
                'group' => $this->group($model),
                'label' => $result->{$this->label($model)},
                'routes' => $this->routes($model),
            ];
        });
    }

    private function routes($model)
    {
        $routes = collect(
            $this->models[$model]['permissions']
                ?? $this->routes->keys()
            )->map(function ($route) use ($model) {
                return $this->models[$model]['permissionGroup'].'.'.$route;
            });

        return auth()->user()->role
            ->permissions()
            ->whereIn('name', $routes)
            ->pluck('name')
            ->sortBy(function ($route) {
                return $this->routes->keys()
                    ->search($this->suffix($route));
            })->values()
            ->map(function ($route) {
                return [
                    'name' => $route,
                    'icon' => $this->icon($route),
                ];
            });
    }

    private function words($query)
    {
        return collect(explode(' ', trim($query)))
            ->filter();
    }

    private function group($model)
    {
        return $this->models[$model]['group']
            ?? class_basename($model);
    }

    private function label($model)
    {
        return $this->models[$model]['label']
            ?? config('enso.searchable.defaultLabel');
    }

    private function icon($route)
    {
        return $this->routes[$this->suffix($route)] ?? null;
    }

    private function suffix($route)
    {
        return collect(explode('.', $route))
            ->last();
    }

    private function limit()
    {
        return config('enso.searchable.limit');
    }

    private function scopes($model)
    {
        return $this->models[$model]['scopes']
            ?? [];
    }
}
