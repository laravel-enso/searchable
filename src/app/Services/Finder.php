<?php

namespace LaravelEnso\Searchable\App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use LaravelEnso\Searchable\App\Facades\Search;

class Finder
{
    private Collection $searchArguments;
    private Collection $models;
    private Collection $routes;
    private array $actions;

    public function __construct(string $query)
    {
        $this->searchArguments = $this->searchArguments($query);
        $this->models = Search::all();
        $this->routes = new Collection(config('enso.searchable.routes'));
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
        $query = $model::query();

        $this->addScopes($model, $query);

        $this->searchArguments->each(fn ($argument) => $query
            ->where(fn ($query) => $this->matchArgument($model, $query, $argument))
            ->limit($this->limit()));

        return $query->get();
    }

    private function matchArgument(string $model, Builder $query, string $argument): void
    {
        $this->attributes($model)->each(fn ($attribute) => $query->orWhere(
            fn ($query) => $this->matchAttribute($query, $argument, $attribute)
        ));
    }

    private function matchAttribute(Builder $query, string $argument, string $attribute): void
    {
        $nested = $this->isNested($attribute);

        $query->when($nested, fn ($query) => $this->matchSegments($query, $attribute, $argument))
            ->when(! $nested, fn ($query) => $query->where(
                $attribute, 'like', '%'.$argument.'%'
            ));
    }

    private function matchSegments(Builder $query, string $argument, string $attribute)
    {
        $attributes = (new Collection(explode('.', $attribute)));

        $query->whereHas($attributes->shift(), fn ($query) => $this->matchAttribute(
            $query, $attributes->implode('.'), $argument)
        );
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
            ?? (new Collection(explode('_', Str::snake(class_basename($model)))))
                ->map(fn ($word) => Str::ucfirst($word))->implode(' ');
    }

    private function label($result, $model): string
    {
        $label = $this->models->get($model)['label']
            ?? config('enso.searchable.defaultLabel');

        return (new Collection(explode('.', $label)))
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

    private function searchArguments($query): Collection
    {
        return (new Collection(explode(' ', trim($query))))->filter();
    }

    private function attributes($model): Collection
    {
        return new Collection($this->models->get($model)['attributes']);
    }

    private function routes($model): Collection
    {
        return (new Collection(
            $this->models->get($model)['permissions'] ?? $this->routes->keys()
        ))->map(fn ($route) => $this->models->get($model)['permissionGroup'].'.'.$route);
    }

    private function icon($route): ?string
    {
        return $this->routes[$this->suffix($route)] ?? null;
    }

    private function suffix($route): string
    {
        return (new Collection(explode('.', $route)))->last();
    }

    private function limit(): int
    {
        return config('enso.searchable.limit');
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
