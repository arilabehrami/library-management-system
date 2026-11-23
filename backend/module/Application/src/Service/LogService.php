<?php

declare(strict_types=1);

namespace Application\Service;

use Application\Entity\SystemLog;
use Application\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class LogService
{
    public function __construct(
        private readonly EntityManagerInterface $em
    ) {
    }

    public function list(User $actor, ?string $entityType, ?string $actionType, int $limit): array
    {
        if ($actor->getRole() !== 'super_admin') {
            throw new \DomainException('Vetëm super admin mund t\'i shohë logjet.');
        }

        $qb = $this->em->createQueryBuilder()
            ->select('s')
            ->from(SystemLog::class, 's')
            ->orderBy('s.timestamp', 'DESC')
            ->setMaxResults(max(1, min($limit, 500)));

        if ($entityType !== null && $entityType !== '') {
            $qb->andWhere('s.entityType = :et')->setParameter('et', $entityType);
        }
        if ($actionType !== null && $actionType !== '') {
            $qb->andWhere('s.actionType = :at')->setParameter('at', $actionType);
        }

        return $qb->getQuery()->getResult();
    }
}