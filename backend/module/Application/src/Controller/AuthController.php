<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Service\AuthService;
use Laminas\Http\PhpEnvironment\Response;
use Laminas\Mvc\Controller\AbstractActionController;

final class AuthController extends AbstractActionController
{
    public function __construct(
        private readonly AuthService $auth
    ) {
    }

    public function loginAction(): Response
    {
        if (!$this->getRequest()->isPost()) {
            return $this->json(['error' => 'Metoda nuk lejohet.'], 405);
        }

        $data = json_decode($this->getRequest()->getContent(), true);
        if (!is_array($data) || !isset($data['email'], $data['password'])) {
            return $this->json(['error' => 'Duhen email dhe password.'], 400);
        }

        try {
            $result = $this->auth->login(
                (string) $data['email'],
                (string) $data['password'],
                new \DateTimeImmutable()
            );
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], 401);
        }

        return $this->json($result, 200);
    }

    private function json(array $payload, int $status): Response
    {
        $response = $this->getResponse();
        $response->setStatusCode($status);
        $response->getHeaders()->addHeaderLine('Content-Type', 'application/json');
        $response->setContent(json_encode($payload, JSON_THROW_ON_ERROR));

        return $response;
    }
}