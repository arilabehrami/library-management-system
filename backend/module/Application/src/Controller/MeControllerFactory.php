<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Service\AuthService;
use Application\Service\LoanQueryService;
use Psr\Container\ContainerInterface;

final class MeControllerFactory
{
    public function __invoke(ContainerInterface $container): MeController
    {
        $em = $container->get('doctrine.entitymanager.orm_default');

        return new MeController(
            $container->get(AuthService::class),
            new LoanQueryService($em)
        );
    }
}