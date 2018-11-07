<?php

namespace LaravelEnso\Searchable\app\Classes;

use Illuminate\Support\Str;

class Finder
{
    private $words;
    private $models;
    private $routes;
    private $results;
    private $actions;

    public function __construct(string $query)
    {
        $this->words = $this->words($query);
        $this->models = collect(config('enso.searchable.models'));
        $this->routes = collect(config('enso.searchable.routes'));
        $this->results = collect();
        $this->actions = [];
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
        $query = $model::query();

        $this->addScopes($model, $query);

        $this->words->each(function ($word) use ($model, $query) {
            $query->where(function ($query) use ($model, $word) {
                $this->match($model, $query, $word);
            })->limit($this->limit());
        });

        return $query->get();
    }

    private function match($model, $query, $word)
    {
        $this->attributes($model)
            ->each(function ($attribute) use ($query, $model, $word) {
                $this->isNested($attribute)
                    ? $this->where($query, $attribute, $word)
                    : $query->orWhere($attribute, 'like', '%'.$word.'%');
            });
    }

    private function where($query, $attribute, $word)
    {
        if ($this->isNested($attribute)) {
            $attributes = collect(explode('.', $attribute));

            $query->orWhere(function ($query) use ($attributes, $word) {
                $query->whereHas($attributes->shift(), function ($query) use ($attributes, $word) {
                    $this->where($query, $attributes->implode('.'), $word);
                });
            });

            return;
        }

        $query->where($attribute, 'like', '%'.$word.'%');
    }

    private function addScopes($model, $query)
    {
        $this->scopes($model)
            ->each(function ($scope) use ($query) {
                $query->{$scope}();
            });
    }

    private function map($results, $model)
    {
        return $results->map(function ($result) use ($model) {
            return [
                'param' => $this->routeParam($result, $model),
                'group' => $this->group($model),
                'label' => $this->label($result, $model),
                'routes' => $this->actions($model),
            ];
        });
    }

    private function actions($model)
    {
        if (! isset($this->actions[$model])) {
            $this->actions[$model] = $this->permissions($model);
        }

        return $this->actions[$model];
    }

    private function permissions($model)
    {
        return auth()->user()->role
            ->permissions()
            ->whereIn('name', $this->routes($model))
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

    private function attributes($model)
    {
        return collect($this->models[$model]['attributes']);
    }

    private function label($result, $model)
    {
        $label = $this->models[$model]['label']
            ?? config('enso.searchable.defaultLabel');

        return collect(explode('.', $label))
            ->reduce(function ($result, $attribute) {
                return $result->{$attribute};
            }, $result);
    }

    private function routeParam($result, $model)
    {
        $param = isset($this->models[$model]['routeParam'])
            ? key($this->models[$model]['routeParam'])
            : Str::camel(class_basename($model));

        $key = isset($this->models[$model]['routeParam'])
            ? $this->models[$model]['routeParam'][$param]
            : $result->getKeyName();

        return [$param => $result->{$key}];
    }

    private function routes($model)
    {
        return collect(
                $this->models[$model]['permissions'] ?? $this->routes->keys()
            )->map(function ($route) use ($model) {
                return $this->models[$model]['permissionGroup'].'.'.$route;
            });
    }

    private function group($model)
    {
        return $this->models[$model]['group']
            ?? collect(explode('_', snake_case(class_basename($model))))
                ->map(function ($word) {
                    return ucfirst($word);
                })->implode(' ');
    }

    private function icon($route)
    {
        return $this->routes[$this->suffix($route)] ?? null;
    }

    private function suffix($route)
    {
        return collect(explode('.', $route))->last();
    }

    private function limit()
    {
        return config('enso.searchable.limit');
    }

    private function scopes($model)
    {
        return collect($this->models[$model]['scopes'] ?? []);
    }

    private function isNested($attribute)
    {
        return Str::contains($attribute, '.');
    }
}
