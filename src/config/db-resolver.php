<?php

return [
        'database' => [
                       'default' => config('database.default'),
                       'tenant' => 'demo',
                      ],
        'user' => env('USER'),

        'cofigurationPath' => base_path('supervisor'),

        'processName' => 'elimuswift-',

        'numProcs' => 4,

        'basePath' => base_path(),

        'Tenant-Header-Name' => 'tenant',
       ];
