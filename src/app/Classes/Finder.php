<?php

namespace LaravelEnso\Searchable\app\Classes;

use LaravelEnso\PermissionManager\app\Models\Permission;

class Finder
{
    private const Routes = ['index', 'show', 'edit'];

    private $words;
    private $models;
    private $limit;
    private $results;

    public function __construct(string $query)
    {
        $this->words = $this->words($query);
        $this->models = collect(config('enso.searchable.models'));
        $this->limit = config('enso.searchable.limit');
        $this->results = collect();
    }

    public function search()
    {
        $this->query();

        return $this->results;
    }

    private function query()
    {
        $this->models->keys()
            ->each(function ($model) {
                $results = $model::where(function ($query) use ($model) {
                    $this->words->each(function ($word) use ($query, $model) {
                        collect($this->models[$model]['attributes'])
                            ->each(function ($attribute) use ($query, $word) {
                                $query->orWhere($attribute, 'like', '%'.$word.'%');
                            });
                    });
                })->get()
                ->map(function ($result) use ($model) {
                    return [
                        'id' => $result->getKey(),
                        'groupLabel' => $this->models[$model]['groupLabel'],
                        'label' => $result->{$this->models[$model]['itemLabel']},
                        'routes' => $this->routes($model),
                    ];
                });

                if ($results->count()) {
                    $this->results = $this->results->merge($results);
                }
            });
    }

    private function routes($model)
    {
        $routes = collect($this->models[$model]['permissions'] ?? self::Routes)
            ->map(
                function ($route) use ($model) {
                    return $this->models[$model]['permissionGroup'].'.'.$route;
                }
        );

        return Permission::whereIn('name', $routes)
            ->pluck('name');
    }

    private function words($query)
    {
        return collect(explode(' ', trim($query)))
            ->filter();
    }
}
