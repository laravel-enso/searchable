<?php

namespace LaravelEnso\Searchable\app\Services;

use Illuminate\Support\Str;
use LaravelEnso\Searchable\app\Facades\Search;

class Finder
{
    private $searchArguments;
    private $models;
    private $routes;
    private $results;
    private $actions;

    public function __construct(string $query)
    {
        $this->searchArguments = $this->searchArguments($query);
        $this->models = Search::all();
        $this->routes = collect(config('enso.searchable.routes'));
        $this->results = collect();
        $this->actions = [];
    }

    public function search()
    {
        $this->models->keys()->each(function ($model) {
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

        $this->searchArguments->each(function ($argument) use ($model, $query) {
            $query->where(function ($query) use ($model, $argument) {
                $this->match($model, $query, $argument);
            })->limit($this->limit());
        });

        return $query->get();
    }

    private function match($model, $query, $argument)
    {
        $this->attributes($model)->each(function ($attribute) use ($query, $argument) {
            return $this->isNested($attribute)
                ? $this->whereHasRelation($query, $attribute, $argument)
                : $query->orWhere($attribute, 'like', '%'.$argument.'%');
        });
    }

    private function whereHasRelation($query, $attribute, $argument)
    {
        if (! $this->isNested($attribute)) {
            $query->where($attribute, 'like', '%'.$argument.'%');

            return;
        }

        $attributes = collect(explode('.', $attribute));

        $query->orWhere(function ($query) use ($attributes, $argument) {
            $query->whereHas($attributes->shift(), function ($query) use ($attributes, $argument) {
                $this->whereHasRelation($query, $attributes->implode('.'), $argument);
            });
        });
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

    private function searchArguments($query)
    {
        return collect(explode(' ', trim($query)))->filter();
    }

    private function attributes($model)
    {
        return collect($this->models->get($model)['attributes']);
    }

    private function label($result, $model)
    {
        $label = $this->models->get($model)['label']
            ?? config('enso.searchable.defaultLabel');

        return collect(explode('.', $label))
            ->reduce(function ($result, $attribute) {
                return (string) $result->{$attribute};
            }, $result);
    }

    private function routeParam($result, $model)
    {
        $param = isset($this->models->get($model)['routeParam'])
            ? key($this->models->get($model)['routeParam'])
            : Str::camel(class_basename($model));

        $key = isset($this->models->get($model)['routeParam'])
            ? $this->models->get($model)['routeParam'][$param]
            : $result->getKeyName();

        return [$param => $result->{$key}];
    }

    private function routes($model)
    {
        return collect(
                $this->models->get($model)['permissions'] ?? $this->routes->keys()
            )->map(function ($route) use ($model) {
                return $this->models->get($model)['permissionGroup'].'.'.$route;
            });
    }

    private function group($model)
    {
        return $this->models->get($model)['group']
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
        return collect($this->models->get($model)['scopes'] ?? []);
    }

    private function isNested($attribute)
    {
        return Str::contains($attribute, '.');
    }
}
