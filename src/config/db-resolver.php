<?php

return [
        'database' => [
                       'default' => 'mysql',
                       'tenant' => 'demo',
                      ],
        'user' => env('USER'),

        'cofigurationPath' => base_path('supervisor'),

        'processName' => 'elimuswift-',

        'numProcs' => 4,

        'basePath' => base_path(),
       ];
