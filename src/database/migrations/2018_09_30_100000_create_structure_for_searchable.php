<?php

use LaravelEnso\Migrator\App\Database\Migration;
use LaravelEnso\Permissions\App\Enums\Types;

class CreateStructureForSearchable extends Migration
{
    protected $permissions = [
        ['name' => 'core.search.index', 'description' => 'Search index', 'type' => Types::Read, 'is_default' => true],
    ];
}
