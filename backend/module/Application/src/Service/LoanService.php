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

final class LoanService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly NotificationService $notifications
    ) {
    }

    public function borrow(User $user, int $bookId, \DateTimeImmutable $now): Loan
    {
        return $this->em->wrapInTransaction(function () use ($user, $bookId, $now): Loan {
            $lockedUser = $this->em->find(User::class, $user->getId(), LockMode::PESSIMISTIC_WRITE);
            if ($lockedUser === null || $lockedUser->isDeleted()) {
                throw new \DomainException('Përdoruesi nuk u gjet.');
            }

            $book = $this->em->find(Book::class, $bookId, LockMode::PESSIMISTIC_WRITE);
            if ($book === null || $book->isDeleted()) {
                throw new \DomainException('Libri nuk u gjet.');
            }

            if (!$book->isAvailable()) {
                throw new \DomainException('UNAVAILABLE: Libri nuk është i disponueshëm.');
            }

            if ($this->hasUnpaidPenalty($lockedUser)) {
                throw new \DomainException('Nuk mund të huazosh: ke penalitete të papaguara.');
            }

            $borrowingLimit = (int) $this->setting('borrowing_limit');
            if ($this->countActiveLoans($lockedUser) >= $borrowingLimit) {
                throw new \DomainException("Ke arritur limitin e huazimeve ($borrowingLimit).");
            }

            $borrowFee = $this->setting('borrow_fee');
            $borrowingPeriod = (int) $this->setting('borrowing_period');

            $dueAt = $now->setTime(23, 59, 59)->modify("+{$borrowingPeriod} days");

            $loan = new Loan($lockedUser, $book, $dueAt, $borrowFee);
            $book->decrementStock();
            
            $this->notifications->checkStock($book);

            $this->em->persist($loan);
            $this->em->flush(); 

            $this->em->persist(new Payment(
                user: $lockedUser,
                amount: $borrowFee,
                paymentType: 'borrow_fee',
                paymentMethod: 'online',
                loan: $loan
            ));

            $this->em->persist(new SystemLog(
                actor: $lockedUser,
                actionType: 'loan.created',
                entityType: 'Loan',
                entityId: $loan->getId(),
                details: json_encode([
                    'book_id' => $book->getId(),
                    'fee' => $borrowFee,
                    'due_at' => $dueAt->format(DATE_ATOM),
                ], JSON_THROW_ON_ERROR)
            ));

            $this->em->flush();

            return $loan;
        });
    }

    private function hasUnpaidPenalty(User $user): bool
    {
        $count = $this->em->createQueryBuilder()
            ->select('COUNT(p.id)')
            ->from(Penalty::class, 'p')
            ->join('p.loan', 'l')
            ->where('l.user = :user')
            ->andWhere('p.paid = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count > 0;
    }

    private function countActiveLoans(User $user): int
    {
        $count = $this->em->createQueryBuilder()
            ->select('COUNT(l.id)')
            ->from(Loan::class, 'l')
            ->where('l.user = :user')
            ->andWhere('l.status IN (:statuses)')
            ->setParameter('user', $user)
            ->setParameter('statuses', [LoanStatus::Active, LoanStatus::Overdue])
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count;
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