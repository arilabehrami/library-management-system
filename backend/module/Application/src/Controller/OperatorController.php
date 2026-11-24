<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Entity\Loan;
use Application\Entity\Penalty;
use Application\Entity\User;
use Application\Service\AuthService;
use Application\Service\LoanQueryService;
use Application\Service\OperatorQueryService;
use Laminas\Http\PhpEnvironment\Response;
use Laminas\Mvc\Controller\AbstractActionController;

final class OperatorController extends AbstractActionController
{
    private const STATUSES = ['active', 'overdue', 'awaiting_payment'];

    public function __construct(
        private readonly AuthService $auth,
        private readonly OperatorQueryService $operator,
        private readonly LoanQueryService $loans
    ) {
    }

    public function loansAction(): Response
    {
        $staff = $this->prepare();
        if ($staff instanceof Response) {
            return $staff;
        }

        $status = $this->getRequest()->getQuery('status');
        $status = is_string($status) && $status !== '' ? $status : null;

        if ($status !== null && !in_array($status, self::STATUSES, true)) {
            return $this->json(['error' => 'status duhet: active, overdue ose awaiting_payment.'], 400);
        }

        $now = new \DateTimeImmutable();
        $list = array_map(
            fn(Loan $l) => $this->presentLoan($l, $now),
            $this->operator->loans($status, $now)
        );

        return $this->json(['loans' => $list], 200);
    }

    public function userLoansAction(): Response
    {
        $staff = $this->prepare();
        if ($staff instanceof Response) {
            return $staff;
        }

        try {
            $student = $this->operator->findStudent((int) $this->params()->fromRoute('id'));
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        }

        $now = new \DateTimeImmutable();
        $list = array_map(
            fn(Loan $l) => $this->presentLoan($l, $now),
            $this->loans->historyFor($student)
        );

        return $this->json(['user' => $this->presentUser($student), 'loans' => $list], 200);
    }

    public function userPenaltiesAction(): Response
    {
        $staff = $this->prepare();
        if ($staff instanceof Response) {
            return $staff;
        }

        try {
            $student = $this->operator->findStudent((int) $this->params()->fromRoute('id'));
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        }

        $list = array_map(
            fn(Penalty $p) => $this->presentPenalty($p),
            $this->loans->penaltiesFor($student)
        );

        return $this->json(['user' => $this->presentUser($student), 'penalties' => $list], 200);
    }

    private function prepare(): User|Response
    {
        if (!$this->getRequest()->isGet()) {
            return $this->json(['error' => 'Metoda nuk lejohet.'], 405);
        }

        $header = $this->getRequest()->getHeaders()->get('Authorization');
        if ($header === false) {
            return $this->json(['error' => 'Mungon token-i.'], 401);
        }

        $token = trim(str_replace('Bearer', '', $header->getFieldValue()));

        try {
            $user = $this->auth->userFromToken($token);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], 401);
        }

        if (!$user->isStaff()) {
            return $this->json(['error' => 'Vetëm operatori mund ta shohë këtë.'], 403);
        }

        return $user;
    }

    private function presentUser(User $user): array
    {
        return [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
        ];
    }

    private function presentLoan(Loan $loan, \DateTimeImmutable $now): array
    {
        $status = $loan->getStatus()->value;
        if ($loan->getReturnedAt() === null && $loan->getDueAt() < $now->setTime(0, 0)) {
            $status = 'overdue';
        }

        return [
            'id' => $loan->getId(),
            'user' => $this->presentUser($loan->getUser()),
            'book' => [
                'id' => $loan->getBook()->getId(),
                'title' => $loan->getBook()->getTitle(),
            ],
            'borrowed_at' => $loan->getBorrowedAt()->format(DATE_ATOM),
            'due_at' => $loan->getDueAt()->format(DATE_ATOM),
            'returned_at' => $loan->getReturnedAt()?->format(DATE_ATOM),
            'status' => $status,
            'fee' => $loan->getBorrowFeeAmount(),
            'penalty' => ($penalty = $this->loans->penaltyForLoan($loan)) ? $this->presentPenalty($penalty) : null,
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