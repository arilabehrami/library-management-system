<?php

declare(strict_types=1);

chdir(__DIR__ . '/..');
require 'vendor/autoload.php';

$app = \Laminas\Mvc\Application::init(require 'config/application.config.php');
$em  = $app->getServiceManager()->get('doctrine.entitymanager.orm_default');

$service = new \Application\Service\OverdueService($em);

$overdue   = $service->run(new \DateTimeImmutable());
$reminders = $service->remindPendingPenalties();

echo "Huazime të shënuara si overdue: $overdue" . PHP_EOL;
echo "Kujtesa për penalitet: $reminders" . PHP_EOL;