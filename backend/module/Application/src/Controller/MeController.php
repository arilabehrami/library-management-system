<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Entity\Loan;
use Application\Entity\Penalty;
use Application\Entity\User;
use Application\Service\AuthService;
use Application\Service\LoanQueryService;
use Laminas\Http\PhpEnvironment\Response;
use Laminas\Mvc\Controller\AbstractActionController;

final class MeController extends AbstractActionController
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly LoanQueryService $queries
    ) {
    }

    public function loansAction(): Response
    {
        $user = $this->authenticate();
        if ($user instanceof Response) {
            return $user;
        }

        $now = new \DateTimeImmutable();
        $loans = array_map(fn(Loan $l) => $this->presentLoan($l, $now), $this->queries->activeFor($user));

        return $this->json(['loans' => $loans], 200);
    }

    public function historyAction(): Response
    {
        $user = $this->authenticate();
        if ($user instanceof Response) {
            return $user;
        }

        $now = new \DateTimeImmutable();
        $loans = array_map(fn(Loan $l) => $this->presentLoan($l, $now), $this->queries->historyFor($user));

        return $this->json(['loans' => $loans], 200);
    }

    public function penaltiesAction(): Response
    {
        $user = $this->authenticate();
        if ($user instanceof Response) {
            return $user;
        }

        $penalties = array_map(fn(Penalty $p) => $this->presentPenalty($p), $this->queries->penaltiesFor($user));

        return $this->json(['penalties' => $penalties], 200);
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

    private function presentLoan(Loan $loan, \DateTimeImmutable $now): array
    {
        $status = $loan->getStatus()->value;
        if ($loan->getReturnedAt() === null && $loan->getDueAt() < $now) {
            $status = 'overdue';
        }

        return [
            'id' => $loan->getId(),
            'book' => [
                'id' => $loan->getBook()->getId(),
                'title' => $loan->getBook()->getTitle(),
                'author' => $loan->getBook()->getAuthor(),
            ],
            'borrowed_at' => $loan->getBorrowedAt()->format(DATE_ATOM),
            'due_at' => $loan->getDueAt()->format(DATE_ATOM),
            'returned_at' => $loan->getReturnedAt()?->format(DATE_ATOM),
            'status' => $status,
            'fee' => $loan->getBorrowFeeAmount(),
        ];
    }

    private function presentPenalty(Penalty $penalty): array
    {
        return [
            'id' => $penalty->getId(),
            'loan_id' => $penalty->getLoan()->getId(),
            'amount' => $penalty->getAmount(),
            'days_late' => $penalty->getDaysLate(),
            'paid' => $penalty->isPaid(),
            'paid_at' => $penalty->getPaidAt()?->format(DATE_ATOM),
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