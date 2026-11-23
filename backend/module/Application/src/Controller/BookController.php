<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Entity\Book;
use Application\Entity\User;
use Application\Service\AuthService;
use Application\Service\BookService;
use Laminas\Http\PhpEnvironment\Response;
use Laminas\Mvc\Controller\AbstractActionController;

final class BookController extends AbstractActionController
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly BookService $books
    ) {
    }

    public function listAction(): Response
    {
        $user = $this->authenticate();
        if ($user instanceof Response) {
            return $user;
        }

        $q = $this->getRequest()->getQuery('q');
        $list = array_map(fn(Book $b) => $this->present($b), $this->books->search(is_string($q) ? $q : null));

        return $this->json(['books' => $list], 200);
    }

    public function showAction(): Response
    {
        $user = $this->authenticate();
        if ($user instanceof Response) {
            return $user;
        }

        try {
            $book = $this->books->find((int) $this->params()->fromRoute('id'));
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        }

        return $this->json(['book' => $this->present($book)], 200);
    }

    public function createAction(): Response
    {
        $user = $this->authenticate();
        if ($user instanceof Response) {
            return $user;
        }

        $data = $this->body();
        foreach (['title', 'author', 'isbn', 'category'] as $field) {
            if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
                return $this->json(['error' => "Mungon fusha: $field."], 400);
            }
        }
        if (!isset($data['stock']) || (int) $data['stock'] < 0) {
            return $this->json(['error' => 'Stoku duhet të jetë numër jo negativ.'], 400);
        }

        try {
            $book = $this->books->create($user, [
                'title' => (string) $data['title'],
                'author' => (string) $data['author'],
                'isbn' => trim((string) $data['isbn']),
                'category' => (string) $data['category'],
                'stock' => (int) $data['stock'],
            ], new \DateTimeImmutable());
        } catch (\DomainException $e) {
            return $this->domainError($e);
        }

        return $this->json(['book' => $this->present($book)], 201);
    }

    public function updateAction(): Response
    {
        $user = $this->authenticate();
        if ($user instanceof Response) {
            return $user;
        }

        try {
            $book = $this->books->update(
                $user,
                (int) $this->params()->fromRoute('id'),
                $this->body(),
                new \DateTimeImmutable()
            );
        } catch (\DomainException $e) {
            return $this->domainError($e);
        }

        return $this->json(['book' => $this->present($book)], 200);
    }

    public function deleteAction(): Response
    {
        $user = $this->authenticate();
        if ($user instanceof Response) {
            return $user;
        }

        try {
            $this->books->delete($user, (int) $this->params()->fromRoute('id'), new \DateTimeImmutable());
        } catch (\DomainException $e) {
            return $this->domainError($e);
        }

        return $this->json(['deleted' => true], 200);
    }

    private function body(): array
    {
        $data = json_decode($this->getRequest()->getContent(), true);

        return is_array($data) ? $data : [];
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

    private function domainError(\DomainException $e): Response
    {
        $message = $e->getMessage();
        $status = str_starts_with($message, 'Vetëm') ? 403 : 422;

        return $this->json(['error' => $message], $status);
    }

    private function present(Book $book): array
    {
        return [
            'id' => $book->getId(),
            'title' => $book->getTitle(),
            'author' => $book->getAuthor(),
            'isbn' => $book->getIsbn(),
            'category' => $book->getCategory(),
            'stock' => $book->getStock(),
            'status' => $book->isAvailable() ? 'available' : 'unavailable',
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