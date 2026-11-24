<?php

declare(strict_types=1);

namespace Application\Service;

use Application\Entity\Book;
use Application\Entity\Loan;
use Application\Entity\LoanStatus;
use Application\Entity\Payment;
use Application\Entity\Penalty;
use Application\Entity\SystemLog;
use Application\Entity\SystemSetting;
use Application\Entity\User;
use Application\Service\NotificationService;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

final class ReturnService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly NotificationService $notifications
    ) {
    }

    public function requestReturn(int $loanId, User $actor, \DateTimeImmutable $now): Loan
    {
        return $this->em->wrapInTransaction(function () use ($loanId, $actor, $now): Loan {
            $loan = $this->em->find(Loan::class, $loanId, LockMode::PESSIMISTIC_WRITE);
            if ($loan === null) {
                throw new \DomainException('Huazimi nuk u gjet.');
            }
            $this->em->refresh($loan, LockMode::PESSIMISTIC_WRITE);
            if ($actor->getRole() !== 'student' || $loan->getUser()->getId() !== $actor->getId()) {
                throw new \DomainException('Nuk ke leje të kërkosh kthimin e këtij huazimi.');
            }
            if ($loan->getReturnedAt() !== null) {
                throw new \DomainException('Ky huazim është kthyer tashmë.');
            }
            if ($loan->getReturnRequestedAt() !== null) {
                throw new \DomainException('Kërkesa e kthimit është dërguar tashmë.');
            }

            $loan->requestReturn($now);
            $this->notifications->notifyReturnRequest($loan);
            $this->em->persist(new SystemLog(
                actor: $actor,
                actionType: 'loan.return_requested',
                entityType: 'Loan',
                entityId: $loan->getId(),
                details: json_encode(['book_id' => $loan->getBook()->getId()], JSON_THROW_ON_ERROR)
            ));
            return $loan;
        });
    }

    public function returnLoan(int $loanId, User $actor, \DateTimeImmutable $now): Loan
    {
        if (!$actor->isStaff()) {
            throw new \DomainException('Vetëm operatori mund të procesojë kthimin.');
        }

        return $this->em->wrapInTransaction(function () use ($loanId, $actor, $now): Loan {
            $loan = $this->em->find(Loan::class, $loanId, LockMode::PESSIMISTIC_WRITE);
            if ($loan === null) {
                throw new \DomainException('Huazimi nuk u gjet.');
            }

            $this->em->lock($loan->getUser(), LockMode::PESSIMISTIC_WRITE);

            if ($loan->getReturnedAt() !== null) {
                throw new \DomainException('Ky huazim është kthyer tashmë.');
            }

            $book = $this->em->find(Book::class, $loan->getBook()->getId(), LockMode::PESSIMISTIC_WRITE);
            if ($book === null) {
                throw new \RuntimeException('Libri i huazimit mungon.');
            }
            $book->incrementStock();

            $this->notifications->checkStock($book);

            $daysLate = $this->daysLate($loan->getDueAt(), $now);
            $rate = $this->setting('penalty_rate');
            $hasPenalty = $daysLate > 0 && bccomp($rate, '0', 2) > 0;

            $loan->markReturned($now, $hasPenalty);

            if ($hasPenalty) {
                $penalty = new Penalty($loan, $daysLate, $rate);
                $this->em->persist($penalty);
                $this->em->flush();
                $this->em->persist(new SystemLog(
                    actor: $actor,
                    actionType: 'penalty.created',
                    entityType: 'Penalty',
                    entityId: $penalty->getId(),
                    details: json_encode(['loan_id' => $loan->getId(), 'amount' => $penalty->getAmount()], JSON_THROW_ON_ERROR)
                ));
            }

            $this->em->persist(new SystemLog(
                actor: $actor,
                actionType: 'loan.returned',
                entityType: 'Loan',
                entityId: $loan->getId(),
                details: json_encode([
                    'book_id' => $book->getId(),
                    'stock' => $book->getStock(),
                    'days_late' => $daysLate,
                    'status' => $loan->getStatus()->value,
                ], JSON_THROW_ON_ERROR)
            ));

            $this->em->flush();

            return $loan;
        });
    }

    public function payPenalty(int $loanId, User $actor, \DateTimeImmutable $now): Penalty
    {
        return $this->em->wrapInTransaction(function () use ($loanId, $actor, $now): Penalty {
            $loan = $this->em->find(Loan::class, $loanId, LockMode::PESSIMISTIC_WRITE);
            if ($loan === null) {
                throw new \DomainException('Huazimi nuk u gjet.');
            }

            $this->em->lock($loan->getUser(), LockMode::PESSIMISTIC_WRITE);

            $isOwner = $loan->getUser()->getId() === $actor->getId();
            if (!$isOwner && !$actor->isStaff()) {
                throw new \DomainException('Nuk ke leje të paguash penalitetin e këtij huazimi.');
            }

            $penalty = $this->em->getRepository(Penalty::class)->findOneBy(['loan' => $loan]);
            if ($penalty === null) {
                throw new \DomainException('Ky huazim nuk ka penalitet.');
            }

            if ($penalty->isPaid()) {
                throw new \DomainException('Penaliti është paguar tashmë.');
            }

            $penalty->markPaid($now);

            $this->em->persist(new Payment(
                user: $loan->getUser(),
                amount: $penalty->getAmount(),
                paymentType: 'penalty',
                paymentMethod: $isOwner ? 'online' : 'in_person',
                penalty: $penalty,
                processedBy: $isOwner ? null : $actor
            ));

            if ($loan->getStatus() === LoanStatus::AwaitingPayment) {
                $loan->close();
            }

            $this->em->persist(new SystemLog(
                actor: $actor,
                actionType: 'penalty.paid',
                entityType: 'Penalty',
                entityId: $penalty->getId(),
                details: json_encode(['amount' => $penalty->getAmount()], JSON_THROW_ON_ERROR)
            ));

            $this->em->flush();

            return $penalty;
        });
    }

    private function daysLate(\DateTimeImmutable $dueAt, \DateTimeImmutable $now): int
    {
        $due = $dueAt->setTime(0, 0);
        $today = $now->setTime(0, 0);

        if ($today <= $due) {
            return 0;
        }

        return (int) $due->diff($today)->days;
    }

    private function setting(string $key): string
    {
        $setting = $this->em->getRepository(SystemSetting::class)
            ->findOneBy(['settingKey' => $key]);

        if ($setting === null) {
            throw new \RuntimeException("Mungon cilësimi: $key");
        }

        return $setting->getSettingValue();
    }
}
