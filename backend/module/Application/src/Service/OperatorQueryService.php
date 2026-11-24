<?php

declare(strict_types=1);

namespace Application\Service;

use Application\Entity\Loan;
use Application\Entity\LoanStatus;
use Application\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class OperatorQueryService
{
    public function __construct(
        private readonly EntityManagerInterface $em
    ) {
    }

    public function findStudent(int $id): User
    {
        $user = $this->em->find(User::class, $id);
        if ($user === null || $user->getRole() !== 'student') {
            throw new \DomainException('Studenti nuk u gjet.');
        }

        return $user;
    }

    public function loans(?string $status, \DateTimeImmutable $now): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('l')
            ->from(Loan::class, 'l')
            ->orderBy('l.dueAt', 'ASC');

        if ($status === 'active') {
            $qb->andWhere('l.returnedAt IS NULL')
               ->andWhere('l.dueAt >= :now')
               ->setParameter('now', $now->setTime(0, 0));
        } elseif ($status === 'overdue') {
            $qb->andWhere('l.returnedAt IS NULL')
               ->andWhere('l.dueAt < :now')
               ->setParameter('now', $now->setTime(0, 0));
        } elseif ($status === 'awaiting_payment') {
            $qb->andWhere('l.status = :st')
               ->setParameter('st', LoanStatus::AwaitingPayment);
        }

        return $qb->getQuery()->getResult();
    }
}