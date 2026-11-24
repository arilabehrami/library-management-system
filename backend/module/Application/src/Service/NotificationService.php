<?php

declare(strict_types=1);

namespace Application\Service;

use Application\Entity\Book;
use Application\Entity\Loan;
use Application\Entity\Notification;
use Application\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class NotificationService
{
    private const LOW_STOCK_THRESHOLD = 2;

    public function __construct(
        private readonly EntityManagerInterface $em
    ) {
    }

    public function refreshAlerts(): void
    {
        $overdue = new OverdueService($this->em);
        $overdue->run(new \DateTimeImmutable());
        $overdue->remindPendingPenalties();
    }

    public function notifyReturnRequest(Loan $loan): void
    {
        $staff = $this->em->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->where('u.role IN (:roles)')
            ->andWhere('u.deletedAt IS NULL')
            ->setParameter('roles', ['operator', 'super_admin'])
            ->getQuery()->getResult();

        foreach ($staff as $user) {
            $this->em->persist(new Notification(
                $user,
                'Kërkesë për kthim',
                sprintf('%s kërkon kthimin e "%s". Konfirmoje kthimin vetëm pasi ta pranosh librin fizikisht.', $loan->getUser()->getName(), $loan->getBook()->getTitle()),
                'return_requested'
            ));
        }
    }

    public function checkStock(Book $book): void
    {
        $stock = $book->getStock();
        if ($stock > self::LOW_STOCK_THRESHOLD) {
            return;
        }

        $isOut = $stock === 0;
        $title = $isOut ? 'Libri ka mbaruar' : 'Stok i ulët';
        $message = sprintf('ISBN %s: "%s" ka %d kopje të mbetura.', $book->getIsbn(), $book->getTitle(), $stock);
        $type = $isOut ? 'out_of_stock' : 'low_stock';

        $staff = $this->em->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->where('u.role IN (:roles)')
            ->andWhere('u.deletedAt IS NULL')
            ->setParameter('roles', ['operator', 'super_admin'])
            ->getQuery()
            ->getResult();

        foreach ($staff as $user) {
            $existing = $this->em->createQueryBuilder()
                ->select('n')
                ->from(Notification::class, 'n')
                ->where('n.user = :user')
                ->andWhere('n.type = :type')
                ->andWhere('n.isRead = false')
                ->andWhere('n.message LIKE :prefix')
                ->setParameter('user', $user)
                ->setParameter('type', $type)
                ->setParameter('prefix', 'ISBN ' . $book->getIsbn() . ':%')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            if ($existing !== null) {
                $existing->setMessage($message);
                continue;
            }

            $this->em->persist(new Notification($user, $title, $message, $type));
        }
    }

    public function listFor(User $user): array
    {
        return $this->em->createQueryBuilder()
            ->select('n')
            ->from(Notification::class, 'n')
            ->where('n.user = :user')
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function markRead(User $user, int $notificationId): Notification
    {
        $notification = $this->em->find(Notification::class, $notificationId);
        if ($notification === null || $notification->getUser()->getId() !== $user->getId()) {
            throw new \DomainException('Njoftimi nuk u gjet.');
        }

        $notification->setIsRead(true);
        $this->em->flush();

        return $notification;
    }
}