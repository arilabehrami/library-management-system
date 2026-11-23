<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Entity\SystemLog;
use Application\Entity\User;
use Application\Service\AuthService;
use Application\Service\LogService;
use Application\Service\SettingService;
use Application\Service\UserService;
use Laminas\Http\PhpEnvironment\Response;
use Laminas\Mvc\Controller\AbstractActionController;

final class AdminController extends AbstractActionController
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly UserService $users,
        private readonly SettingService $settings,
        private readonly LogService $logs
    ) {
    }

    public function usersAction(): Response
    {
        $admin = $this->authenticate();
        if ($admin instanceof Response) {
            return $admin;
        }

        try {
            if ($this->getRequest()->isGet()) {
                $list = array_map(fn(User $u) => $this->presentUser($u), $this->users->list($admin));
                return $this->json(['users' => $list], 200);
            }
            if ($this->getRequest()->isPost()) {
                $user = $this->users->create($admin, $this->body(), new \DateTimeImmutable());
                return $this->json(['user' => $this->presentUser($user)], 201);
            }
        } catch (\DomainException $e) {
            return $this->domainError($e);
        }

        return $this->json(['error' => 'Metoda nuk lejohet.'], 405);
    }

    public function userAction(): Response
    {
        $admin = $this->authenticate();
        if ($admin instanceof Response) {
            return $admin;
        }

        $id = (int) $this->params()->fromRoute('id');

        try {
            if ($this->getRequest()->isPut()) {
                $user = $this->users->update($admin, $id, $this->body(), new \DateTimeImmutable());
                return $this->json(['user' => $this->presentUser($user)], 200);
            }
            if ($this->getRequest()->isDelete()) {
                $this->users->delete($admin, $id, new \DateTimeImmutable());
                return $this->json(['deleted' => true], 200);
            }
        } catch (\DomainException $e) {
            return $this->domainError($e);
        }

        return $this->json(['error' => 'Metoda nuk lejohet.'], 405);
    }

    public function settingsAction(): Response
    {
        $admin = $this->authenticate();
        if ($admin instanceof Response) {
            return $admin;
        }

        try {
            return $this->json(['settings' => $this->settings->all($admin)], 200);
        } catch (\DomainException $e) {
            return $this->domainError($e);
        }
    }

    public function settingAction(): Response
    {
        $admin = $this->authenticate();
        if ($admin instanceof Response) {
            return $admin;
        }

        if (!$this->getRequest()->isPut()) {
            return $this->json(['error' => 'Metoda nuk lejohet.'], 405);
        }

        $body = $this->body();
        if (!isset($body['value'])) {
            return $this->json(['error' => 'Duhet value.'], 400);
        }

        try {
            $setting = $this->settings->update(
                $admin,
                (string) $this->params()->fromRoute('key'),
                (string) $body['value'],
                new \DateTimeImmutable()
            );
        } catch (\DomainException $e) {
            return $this->domainError($e);
        }

        return $this->json(['key' => $setting->getSettingKey(), 'value' => $setting->getSettingValue()], 200);
    }

    public function logsAction(): Response
    {
        $admin = $this->authenticate();
        if ($admin instanceof Response) {
            return $admin;
        }

        $query = $this->getRequest()->getQuery();
        $limit = (int) ($query->get('limit') ?? 100);

        try {
            $list = array_map(
                fn(SystemLog $s) => $this->presentLog($s),
                $this->logs->list(
                    $admin,
                    $query->get('entity_type'),
                    $query->get('action_type'),
                    $limit
                )
            );
        } catch (\DomainException $e) {
            return $this->domainError($e);
        }

        return $this->json(['logs' => $list], 200);
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

    private function body(): array
    {
        $data = json_decode($this->getRequest()->getContent(), true);

        return is_array($data) ? $data : [];
    }

    private function domainError(\DomainException $e): Response
    {
        $message = $e->getMessage();

        if (str_starts_with($message, 'Vetëm')) {
            return $this->json(['error' => $message], 403);
        }
        if (str_contains($message, 'nuk u gjet')) {
            return $this->json(['error' => $message], 404);
        }

        return $this->json(['error' => $message], 422);
    }

    private function presentUser(User $user): array
    {
        return [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'role' => $user->getRole(),
        ];
    }

    private function presentLog(SystemLog $log): array
    {
        return [
            'id' => $log->getId(),
            'actor_id' => $log->getActor()->getId(),
            'action_type' => $log->getActionType(),
            'entity_type' => $log->getEntityType(),
            'entity_id' => $log->getEntityId(),
            'timestamp' => $log->getTimestamp()->format(DATE_ATOM),
            'details' => $log->getDetails(),
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