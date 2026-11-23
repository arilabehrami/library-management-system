<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Service\AuthService;
use Application\Service\LoanService;
use Application\Service\ReturnService;
use Application\Service\NotificationService;
use Psr\Container\ContainerInterface;

final class LoanControllerFactory
{
    public function __invoke(ContainerInterface $container): LoanController
    {
        $em = $container->get('doctrine.entitymanager.orm_default');

        return new LoanController(
            $container->get(AuthService::class),
            new LoanService($em, new NotificationService($em)),
            new ReturnService($em, new NotificationService($em))
        );
    }
}