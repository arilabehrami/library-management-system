<?php

declare(strict_types=1);

chdir(__DIR__ . '/..');
require 'vendor/autoload.php';

if (count($argv) !== 3 || !filter_var($argv[2], FILTER_VALIDATE_EMAIL)) {
    exit("Usage: php bin/create-admin.php Name email@example.com\n");
}
fwrite(STDOUT, "Password (minimum 8 characters): ");
$password = trim(fgets(STDIN));
if (strlen($password) < 8) {
    exit("Password is too short.\n");
}
$app = \Laminas\Mvc\Application::init(require 'config/application.config.php');
$em = $app->getServiceManager()->get('doctrine.entitymanager.orm_default');
$email = strtolower(trim($argv[2]));
if ($em->getRepository(\Application\Entity\User::class)->findOneBy(['email' => $email])) {
    exit("Email already exists.\n");
}
$user = new \Application\Entity\User($argv[1], $email, password_hash($password, PASSWORD_ARGON2ID), 'super_admin');
$em->persist($user);
$em->flush();
echo "Super Admin created.\n";
