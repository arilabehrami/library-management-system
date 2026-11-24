<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Entity\Notification;
use Application\Entity\User;
use Application\Service\AuthService;
use Application\Service\NotificationService;
use Laminas\Http\PhpEnvironment\Response;
use Laminas\Mvc\Controller\AbstractActionController;

final class NotificationController extends AbstractActionController
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly NotificationService $notifications
    ) {
    }

    public function listAction(): Response
    {
        if (!$this->getRequest()->isGet()) {
            return $this->json(['error' => 'Metoda nuk lejohet.'], 405);
        }

        $user = $this->authenticate();
        if ($user instanceof Response) {
            return $user;
        }

        $this->notifications->refreshAlerts();
        $list = array_map(fn(Notification $n) => $this->present($n), $this->notifications->listFor($user));

        return $this->json(['notifications' => $list], 200);
    }

    public function readAction(): Response
    {
        if (!$this->getRequest()->isPost()) {
            return $this->json(['error' => 'Metoda nuk lejohet.'], 405);
        }
        $user = $this->authenticate();
        if ($user instanceof Response) {
            return $user;
        }

        try {
            $n = $this->notifications->markRead($user, (int) $this->params()->fromRoute('id'));
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        }

        return $this->json(['notification' => $this->present($n)], 200);
    }

    private function authenticate(): User|Response
    {
        $header = $this->getRequest()->getHeaders()->get('Authorization');
        if ($header === false) {
            return $this->json(['error' => 'Mungon token-i.'], 401);
        }

        $token = trim(str_replace('Bearer', '', $header->getFieldValue()));

        try {
            return $this->auth->userFromToken($token);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], 401);
        }
    }

    private function present(Notification $n): array
    {
        return [
            'id' => $n->getId(),
            'title' => $n->getTitle(),
            'message' => $n->getMessage(),
            'type' => $n->getType(),
            'is_read' => $n->isRead(),
            'created_at' => $n->getCreatedAt()->format(DATE_ATOM),
        ];
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