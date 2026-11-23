<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Service\AuthService;
use Application\Service\NotificationService;
use Psr\Container\ContainerInterface;

final class NotificationControllerFactory
{
    public function __invoke(ContainerInterface $container): NotificationController
    {
        $em = $container->get('doctrine.entitymanager.orm_default');

        return new NotificationController(
            $container->get(AuthService::class),
            new NotificationService($em)
        );
    }
}