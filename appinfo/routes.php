<?php

return [
    'routes' => [
        ['name' => 'page#index',   'url' => '/',            'verb' => 'GET'],
        ['name' => 'page#stats',   'url' => '/auswertung',  'verb' => 'GET'],
        ['name' => 'rsvp#upsert',  'url' => '/api/rsvp',    'verb' => 'POST'],
        ['name' => 'admin#index',  'url' => '/admin',        'verb' => 'GET'],
        ['name' => 'admin#save',   'url' => '/admin/save',   'verb' => 'POST'],
    ],
];