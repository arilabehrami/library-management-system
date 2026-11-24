<?php

return [
    'doctrine' => [
        'migrations_configuration' => [
            'orm_default' => [
                'migrations_paths' => [
                    'DoctrineMigrations' => 'data/doctrine/migrations',
                ],
                'table_storage' => [
                    'table_name' => 'doctrine_migration_versions',
                    'version_column_name' => 'version',
                ],
            ],
        ],
    ],
];