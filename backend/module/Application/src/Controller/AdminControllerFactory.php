<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Service\AuthService;
use Application\Service\LogService;
use Application\Service\SettingService;
use Application\Service\UserService;
use Psr\Container\ContainerInterface;

final class AdminControllerFactory
{
    public function __invoke(ContainerInterface $container): AdminController
    {
        $em = $container->get('doctrine.entitymanager.orm_default');

        return new AdminController(
            $container->get(AuthService::class),
            new UserService($em),
            new SettingService($em),
            new LogService($em)
        );
    }
}