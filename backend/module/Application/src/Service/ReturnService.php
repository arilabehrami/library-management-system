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
            $hasPenalty = $daysLate > 0;

            $loan->markReturned($now, $hasPenalty);

            if ($hasPenalty) {
                $this->em->persist(new Penalty($loan, $daysLate, $this->setting('penalty_rate')));
            }

            $this->em->persist(new SystemLog(
                actor: $actor,
                actionType: 'loan.returned',
                entityType: 'Loan',
                entityId: $loan->getId(),
                details: json_encode([
                    'book_id' => $book->getId(),
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
                loan: $loan,
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