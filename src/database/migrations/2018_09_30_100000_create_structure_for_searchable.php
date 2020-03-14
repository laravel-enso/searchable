<?php

use LaravelEnso\Migrator\App\Database\Migration;

class CreateStructureForSearchable extends Migration
{
    protected $permissions = [
        ['name' => 'core.search.index', 'description' => 'Search index', 'is_default' => true],
    ];
}
