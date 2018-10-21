<?php

use LaravelEnso\People\app\Models\Person;

return [
    'defaultLabel' => 'name',
    'routes' => [
        'show' => 'eye',
        'edit' => 'pencil-alt',
        'index' => 'list-ul',
    ],
    'limit' => 10,
    'models' => [
        Person::class => [
            'group' => 'Person',
            'attributes' => ['name', 'appellative', 'email', 'phone'],
            'label' => 'name',
            'permissionGroup' => 'administration.people',
        ],
    ],
];
