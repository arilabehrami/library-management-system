<?php

declare(strict_types=1);

namespace Application\Service;

use Application\Entity\Loan;
use Application\Entity\LoanStatus;
use Application\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;

final class OverdueService
{
    public function __construct(
        private readonly EntityManagerInterface $em
    ) {
    }

    public function run(\DateTimeImmutable $now): int
    {
        $loans = $this->em->createQueryBuilder()
            ->select('l')
            ->from(Loan::class, 'l')
            ->where('l.returnedAt IS NULL')
            ->andWhere('l.status = :st')
            ->andWhere('l.dueAt < :now')
            ->setParameter('st', LoanStatus::Active)
            ->setParameter('now', $now->setTime(0, 0))
            ->getQuery()
            ->getResult();

        foreach ($loans as $loan) {
            $loan->markOverdue();

            $this->em->persist(new Notification(
                $loan->getUser(),
                'Libër i vonuar dhe penalitet në pritje',
                sprintf(
                    '"%s" duhej kthyer më %s. Penaliteti rritet për çdo ditë vonesë. Dorëzo librin te operatori për llogaritjen dhe pagesën përfundimtare.',
                    $loan->getBook()->getTitle(),
                    $loan->getDueAt()->format('d.m.Y')
                ),
                'overdue'
            ));
        }

        $this->em->flush();

        return count($loans);
    }

    public function remindPendingPenalties(): int
    {
        $loans = $this->em->createQueryBuilder()
            ->select('l')
            ->from(Loan::class, 'l')
            ->where('l.status = :st')
            ->setParameter('st', LoanStatus::AwaitingPayment)
            ->getQuery()
            ->getResult();

        $count = 0;
        foreach ($loans as $loan) {
            $user = $loan->getUser();

            $existing = $this->em->createQueryBuilder()
                ->select('n')
                ->from(Notification::class, 'n')
                ->where('n.user = :user')
                ->andWhere('n.type = :type')
                ->andWhere('n.isRead = false')
                ->setParameter('user', $user)
                ->setParameter('type', 'penalty_reminder')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            if ($existing !== null) {
                continue;
            }

            $this->em->persist(new Notification(
                $user,
                'Penalitet i papaguar',
                'Ke penalitet të papaguar për "' . $loan->getBook()->getTitle() . '". Nuk mund të huazosh deri sa ta paguash.',
                'penalty_reminder'
            ));
            $count++;
        }

        $this->em->flush();

        return $count;
    }
}