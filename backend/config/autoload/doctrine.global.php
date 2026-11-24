<?php

return [
    'doctrine' => [
        'connection' => [
            'orm_default' => [
                'driverClass' => \Doctrine\DBAL\Driver\PDO\PgSQL\Driver::class,
                'params' => [
                    'host'    => 'localhost',
                    'port'    => 5432,
                    'dbname'  => 'library_management',
                ],
            ],
        ],
        'driver' => [
            'orm_default' => [
                'class' => \Doctrine\ORM\Mapping\Driver\AttributeDriver::class,
                'paths' => [__DIR__ . '/../../module/Application/src/Entity'],
            ],
        ],
        'configuration' => [
            'orm_default' => [
                'metadata_cache'  => 'array',
                'query_cache'     => 'array',
                'result_cache'    => 'array',
                'driver'          => 'orm_default',
                'generate_proxies' => true,
                'proxy_dir'       => 'data/DoctrineORMModule/Proxy',
                'proxy_namespace' => 'DoctrineORMModule\Proxy',
            ],
        ],
        'entitymanager' => [
            'orm_default' => [
                'connection'    => 'orm_default',
                'configuration' => 'orm_default',
            ],
        ],
        'migrations_configuration' => [
            'orm_default' => [
                'migrations_paths' => [
                    'DoctrineMigrations' => 'data/doctrine/migrations',
                ],
                'table_storage' => [
                    'table_name'           => 'doctrine_migration_versions',
                    'version_column_name'  => 'version',
                ],
            ],
        ],
    ],
];