<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Service\AuthService;
use Application\Service\BookService;
use Application\Service\NotificationService;
use Psr\Container\ContainerInterface;

final class BookControllerFactory
{
    public function __invoke(ContainerInterface $container): BookController
    {
        $em = $container->get('doctrine.entitymanager.orm_default');

        return new BookController(
            $container->get(AuthService::class),
            new BookService($em, new NotificationService($em))
        );
    }
}