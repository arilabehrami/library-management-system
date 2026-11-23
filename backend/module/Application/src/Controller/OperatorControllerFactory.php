<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Service\AuthService;
use Application\Service\LoanQueryService;
use Application\Service\OperatorQueryService;
use Psr\Container\ContainerInterface;

final class OperatorControllerFactory
{
    public function __invoke(ContainerInterface $container): OperatorController
    {
        $em = $container->get('doctrine.entitymanager.orm_default');

        return new OperatorController(
            $container->get(AuthService::class),
            new OperatorQueryService($em),
            new LoanQueryService($em)
        );
    }
}