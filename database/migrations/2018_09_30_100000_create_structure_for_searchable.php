<?php

use LaravelEnso\Migrator\Database\Migration;

return new class extends Migration
{
    protected array $permissions = [
        ['name' => 'core.search.index', 'description' => 'Search index', 'is_default' => true],
    ];
};
