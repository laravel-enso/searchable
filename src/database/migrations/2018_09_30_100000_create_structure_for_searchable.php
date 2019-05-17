<?php

use LaravelEnso\Migrator\app\Database\Migration;

class CreateStructureForSearchable extends Migration
{
    protected $permissions = [
        ['name' => 'core.search.index', 'description' => 'Search index', 'type' => 0, 'is_default' => true],
    ];
}
