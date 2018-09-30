<?php

use LaravelEnso\StructureManager\app\Classes\StructureMigration;

class CreateStructureForSearchable extends StructureMigration
{
    protected $permissionGroup = [
        'name' => 'core.search', 'description' => 'System Search permissions group',
    ];

    protected $permissions = [
        ['name' => 'core.search.index', 'description' => 'Search index', 'type' => 0, 'is_default' => true],
    ];
}
