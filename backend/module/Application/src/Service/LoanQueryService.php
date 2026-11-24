<?php

declare(strict_types=1);

namespace Application\Service;

use Application\Entity\Loan;
use Application\Entity\Penalty;
use Application\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class LoanQueryService
{
    public function __construct(
        private readonly EntityManagerInterface $em
    ) {
    }

    public function penaltyForLoan(Loan $loan): ?Penalty
    {
        return $this->em->getRepository(Penalty::class)->findOneBy(['loan' => $loan]);
    }

    public function policy(): array
    {
        $out = [];
        foreach ($this->em->getRepository(\Application\Entity\SystemSetting::class)->findAll() as $setting) {
            $out[$setting->getSettingKey()] = $setting->getSettingValue();
        }
        return $out;
    }

    public function activeFor(User $user): array
    {
        return $this->em->createQueryBuilder()
            ->select('l')
            ->from(Loan::class, 'l')
            ->where('l.user = :user')
            ->andWhere('l.returnedAt IS NULL')
            ->setParameter('user', $user)
            ->orderBy('l.dueAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function historyFor(User $user): array
    {
        return $this->em->createQueryBuilder()
            ->select('l')
            ->from(Loan::class, 'l')
            ->where('l.user = :user')
            ->setParameter('user', $user)
            ->orderBy('l.borrowedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function penaltiesFor(User $user): array
    {
        return $this->em->createQueryBuilder()
            ->select('p')
            ->from(Penalty::class, 'p')
            ->join('p.loan', 'l')
            ->where('l.user = :user')
            ->setParameter('user', $user)
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}