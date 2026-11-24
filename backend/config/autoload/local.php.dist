<?php

return [
    'auth' => [
        'jwt_secret' => getenv('JWT_SECRET') ?: '',
        'jwt_ttl' => 3600,
    ],
    'doctrine' => [
        'connection' => [
            'orm_default' => [
                'params' => [
                    'host' => getenv('DB_HOST') ?: 'localhost',
                    'port' => (int) (getenv('DB_PORT') ?: 5432),
                    'dbname' => getenv('DB_NAME') ?: 'library_management',
                    'user' => getenv('DB_USER') ?: 'postgres',
                    'password' => getenv('DB_PASSWORD') ?: '',
                ],
            ],
        ],
    ],
];
