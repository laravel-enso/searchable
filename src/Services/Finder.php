<?php

namespace LaravelEnso\Searchable\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use LaravelEnso\Filters\Services\Search as Service;
use LaravelEnso\Searchable\Facades\Search;

class Finder
{
    private Collection $models;
    private Collection $routes;
    private array $actions;

    public function __construct(private string $search)
    {
        $this->models = Search::all();
        $this->routes = new Collection(Config::get('enso.searchable.routes'));
        $this->actions = [];
    }

    public function search(): Collection
    {
        return $this->models
            ->keys()->reduce(fn ($result, $model) => $result
                ->merge($this->map($model)), new Collection());
    }

    private function map(string $model): Collection
    {
        return $this->query($model)->map(fn ($result) => [
            'param' => $this->routeParam($result, $model),
            'group' => $this->group($model),
            'label' => $this->label($result, $model),
            'routes' => $this->actions($model),
        ]);
    }

    private function query(string $model): Collection
    {
        $config = $this->models[$model];

        if ($config['searchProvider'] ?? null) {
            return $model::search($this->search)
                ->take(Config::get('enso.searchable.limit'))->get();
        }

        $query = $model::query();

        $this->addScopes($model, $query);

        return (new Service($query, $this->attributes($model), $this->search))
            ->relations($this->relations($model))
            ->comparisonOperator(Config::get('enso.select.comparisonOperator'))
            ->handle()
            ->limit(Config::get('enso.searchable.limit'))
            ->get();
    }

    private function addScopes($model, $query): void
    {
        $this->scopes($model)->each(fn ($scope) => $query->{$scope}());
    }

    private function routeParam($result, $model): array
    {
        $param = isset($this->models->get($model)['routeParam'])
            ? key($this->models->get($model)['routeParam'])
            : Str::camel(class_basename($model));

        $key = isset($this->models->get($model)['routeParam'])
            ? $this->models->get($model)['routeParam'][$param]
            : $result->getKeyName();

        return [$param => $result->{$key}];
    }

    private function group($model): string
    {
        return $this->models->get($model)['group']
            ?? Collection::wrap(explode('_', Str::snake(class_basename($model))))
            ->map(fn ($word) => Str::ucfirst($word))->implode(' ');
    }

    private function label($result, $model): string
    {
        $label = $this->models->get($model)['label']
            ?? config('enso.searchable.defaultLabel');

        return Collection::wrap(explode('.', $label))
            ->reduce(fn ($result, $attribute) => (string) $result->{$attribute}, $result);
    }

    private function actions($model): Collection
    {
        return $this->actions[$model] ??= $this->permissions($model);
    }

    private function permissions($model): Collection
    {
        return Auth::user()->role->permissions()
            ->whereIn('name', $this->routes($model))
            ->pluck('name')
            ->sortBy(fn ($route) => $this->routes->keys()->search($this->suffix($route)))
            ->values()->map(fn ($route) => [
                'name' => $route,
                'icon' => $this->icon($route),
            ]);
    }

    private function attributes($model): array
    {
        return Collection::wrap($this->models->get($model)['attributes'])
            ->reject(fn ($attribute) => $this->isNested($attribute))
            ->toArray();
    }

    private function relations($model): array
    {
        return Collection::wrap($this->models->get($model)['attributes'])
            ->filter(fn ($attribute) => $this->isNested($attribute))
            ->toArray();
    }

    private function routes($model): Collection
    {
        return Collection::wrap(
            $this->models->get($model)['permissions'] ?? $this->routes->keys()
        )->map(fn ($route) => $this->models->get($model)['permissionGroup'].'.'.$route);
    }

    private function icon($route): ?string
    {
        return $this->routes[$this->suffix($route)] ?? null;
    }

    private function suffix($route): string
    {
        return Collection::wrap(explode('.', $route))->last();
    }

    private function scopes($model): Collection
    {
        return new Collection($this->models->get($model)['scopes'] ?? []);
    }

    private function isNested($attribute): bool
    {
        return Str::contains($attribute, '.');
    }
}
