<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Entity\Loan;
use Application\Entity\User;
use Application\Service\AuthService;
use Application\Service\LoanService;
use Application\Service\ReturnService;
use Laminas\Http\PhpEnvironment\Response;
use Laminas\Mvc\Controller\AbstractActionController;

final class LoanController extends AbstractActionController
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly LoanService $loans,
        private readonly ReturnService $returns
    ) {
    }

    public function createAction(): Response
    {
        if (!$this->getRequest()->isPost()) {
            return $this->json(['error' => 'Metoda nuk lejohet.'], 405);
        }

        $user = $this->authenticate();
        if ($user instanceof Response) {
            return $user;
        }

        $data = json_decode($this->getRequest()->getContent(), true);
        $bookId = is_array($data) ? (int) ($data['book_id'] ?? 0) : 0;
        if ($bookId <= 0) {
            return $this->json(['error' => 'Duhet book_id.'], 400);
        }

        try {
            $loan = $this->loans->borrow($user, $bookId, new \DateTimeImmutable());
        } catch (\DomainException $e) {
            return $this->domainError($e);
        }

        return $this->json(['loan' => $this->presentLoan($loan)], 201);
    }

    public function requestReturnAction(): Response
    {
        if (!$this->getRequest()->isPost()) {
            return $this->json(['error' => 'Metoda nuk lejohet.'], 405);
        }
        $user = $this->authenticate();
        if ($user instanceof Response) {
            return $user;
        }
        try {
            $loan = $this->returns->requestReturn(
                (int) $this->params()->fromRoute('id'),
                $user,
                new \DateTimeImmutable()
            );
        } catch (\DomainException $e) {
            return $this->domainError($e);
        }
        return $this->json(['loan' => $this->presentLoan($loan)], 200);
    }

    public function returnAction(): Response
    {
        if (!$this->getRequest()->isPost()) {
            return $this->json(['error' => 'Metoda nuk lejohet.'], 405);
        }

        $user = $this->authenticate();
        if ($user instanceof Response) {
            return $user;
        }

        try {
            $loan = $this->returns->returnLoan(
                (int) $this->params()->fromRoute('id'),
                $user,
                new \DateTimeImmutable()
            );
        } catch (\DomainException $e) {
            return $this->domainError($e);
        }

        return $this->json(['loan' => $this->presentLoan($loan)], 200);
    }

    public function payPenaltyAction(): Response
    {
        if (!$this->getRequest()->isPost()) {
            return $this->json(['error' => 'Metoda nuk lejohet.'], 405);
        }

        $user = $this->authenticate();
        if ($user instanceof Response) {
            return $user;
        }

        try {
            $penalty = $this->returns->payPenalty(
                (int) $this->params()->fromRoute('id'),
                $user,
                new \DateTimeImmutable()
            );
        } catch (\DomainException $e) {
            return $this->domainError($e);
        }

        return $this->json([
            'penalty' => [
                'id' => $penalty->getId(),
                'amount' => $penalty->getAmount(),
                'days_late' => $penalty->getDaysLate(),
                'paid' => $penalty->isPaid(),
                'paid_at' => $penalty->getPaidAt()?->format(DATE_ATOM),
            ],
        ], 200);
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

        if (str_starts_with($message, 'UNAVAILABLE:')) {
            return $this->json([
                'error' => trim(substr($message, strlen('UNAVAILABLE:'))),
                'code' => 'unavailable',
            ], 409);
        }

        if (str_starts_with($message, 'Nuk ke leje') || str_starts_with($message, 'Vetëm')) {
            return $this->json(['error' => $message], 403);
        }

        return $this->json(['error' => $message], 422);
    }

    private function presentLoan(Loan $loan): array
    {
        return [
            'id' => $loan->getId(),
            'book_id' => $loan->getBook()->getId(),
            'user_id' => $loan->getUser()->getId(),
            'borrowed_at' => $loan->getBorrowedAt()->format(DATE_ATOM),
            'due_at' => $loan->getDueAt()->format(DATE_ATOM),
            'returned_at' => $loan->getReturnedAt()?->format(DATE_ATOM),
            'return_requested_at' => $loan->getReturnRequestedAt()?->format(DATE_ATOM),
            'status' => $loan->getStatus()->value,
            'fee' => $loan->getBorrowFeeAmount(),
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