<?php


return [
    'laminas-developer-tools' => [

        'profiler' => [

            'enabled' => true,

            'strict' => true,

            'flush_early' => false,

            'cache_dir' => 'data/cache',

            'matcher' => [],

            'collectors' => [],
        ],
        'events' => [

            'enabled' => true,

            'collectors' => [],

            'identifiers' => [],
        ],

        'toolbar' => [

            'enabled' => true,

            'auto_hide' => false,

            'position' => 'bottom',

            'version_check' => false,

            'entries' => [],
        ],
    ],
];
