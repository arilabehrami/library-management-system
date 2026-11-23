<?php

declare(strict_types=1);

namespace Application\Service;

use Psr\Container\ContainerInterface;

final class AuthServiceFactory
{
    public function __invoke(ContainerInterface $container): AuthService
    {
        $config = $container->get('config')['auth'];

        return new AuthService(
            $container->get('doctrine.entitymanager.orm_default'),
            $config['jwt_secret'],
            (int) $config['jwt_ttl']
        );
    }
}