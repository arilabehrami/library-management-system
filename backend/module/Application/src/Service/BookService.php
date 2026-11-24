<?php

declare(strict_types=1);

namespace Application\Service;

use Application\Entity\Book;
use Application\Entity\SystemLog;
use Application\Entity\User;
use Application\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;

final class BookService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly NotificationService $notifications
    ) {
    }

    public function search(?string $q): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('b')
            ->from(Book::class, 'b')
            ->where('b.deletedAt IS NULL')
            ->orderBy('b.title', 'ASC');

        $q = $q === null ? '' : trim($q);
        if ($q !== '') {
            $like = '%' . mb_strtolower($q) . '%';
            $qb->andWhere($qb->expr()->orX(
                'LOWER(b.title) LIKE :q',
                'LOWER(b.author) LIKE :q',
                'LOWER(b.category) LIKE :q',
                'b.isbn LIKE :raw'
            ))
            ->setParameter('q', $like)
            ->setParameter('raw', '%' . $q . '%');
        }

        return $qb->getQuery()->getResult();
    }

    public function find(int $id): Book
    {
        $book = $this->em->find(Book::class, $id);
        if ($book === null || $book->isDeleted()) {
            throw new \DomainException('Libri nuk u gjet.');
        }

        return $book;
    }

    public function create(User $actor, array $data, \DateTimeImmutable $now): Book
    {
        $this->requireStaff($actor);
        $this->validate($data);
        $this->assertIsbnFree($data['isbn']);

        return $this->em->wrapInTransaction(function () use ($actor, $data, $now): Book {
            $book = new Book($data['title'], $data['author'], $data['isbn'], $data['category'], $data['stock']);
            $this->em->persist($book);
            $this->em->flush();

            $this->notifications->checkStock($book);
            $this->log($actor, 'book.created', $book->getId(), $now, ['isbn' => $book->getIsbn(), 'stock' => $book->getStock()]);
            $this->em->flush();

            return $book;
        });
    }

    public function update(User $actor, int $id, array $data, \DateTimeImmutable $now): Book
    {
        $this->requireStaff($actor);
        $this->validate($data);

        return $this->em->wrapInTransaction(function () use ($actor, $id, $data, $now): Book {
            $book = $this->em->find(Book::class, $id, \Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE);
            if ($book === null || $book->isDeleted()) {
                throw new \DomainException('Libri nuk u gjet.');
            }

            if (isset($data['isbn']) && $data['isbn'] !== $book->getIsbn()) {
                $this->assertIsbnFree($data['isbn']);
                $book->setIsbn($data['isbn']);
            }
            if (isset($data['title']))    { $book->setTitle($data['title']); }
            if (isset($data['author']))   { $book->setAuthor($data['author']); }
            if (isset($data['category'])) { $book->setCategory($data['category']); }
            if (isset($data['stock']))    { $book->setStock((int) $data['stock']); }

            $this->notifications->checkStock($book);

            $book->updateUpdatedAt();
            $this->log($actor, 'book.updated', $book->getId(), $now, $data);
            $this->em->flush();

            return $book;
        });
    }

    public function delete(User $actor, int $id, \DateTimeImmutable $now): void
    {
        $this->requireStaff($actor);
        $book = $this->find($id);

        $book->delete($now);
        $this->log($actor, 'book.deleted', $book->getId(), $now, null);
        $this->em->flush();
    }

    private function validate(array $data): void
    {
        foreach (['title', 'author', 'isbn', 'category'] as $field) {
            $max = $field === 'isbn' ? 50 : ($field === 'category' ? 100 : 255);
            if (array_key_exists($field, $data) && (!is_string($data[$field]) || trim($data[$field]) === '' || mb_strlen($data[$field]) > $max)) {
                throw new \DomainException("Vlerë e pavlefshme për $field.");
            }
        }
        if (array_key_exists('stock', $data) && (filter_var($data['stock'], FILTER_VALIDATE_INT) === false || (int) $data['stock'] < 0 || (int) $data['stock'] > 2147483647)) {
            throw new \DomainException('Stoku duhet të jetë numër i plotë jo negativ.');
        }
    }

    private function requireStaff(User $actor): void
    {
        if (!$actor->isStaff()) {
            throw new \DomainException('Vetëm operatori mund të menaxhojë librat.');
        }
    }

    private function assertIsbnFree(string $isbn): void
    {
        $exists = $this->em->getRepository(Book::class)->findOneBy(['isbn' => $isbn]);
        if ($exists !== null) {
            throw new \DomainException('Ekziston një libër me këtë ISBN.');
        }
    }

    private function log(User $actor, string $action, ?int $entityId, \DateTimeImmutable $now, mixed $details): void
    {
        $this->em->persist(new SystemLog(
            actor: $actor,
            actionType: $action,
            entityType: 'Book',
            entityId: $entityId ?? 0,
            details: $details === null ? null : json_encode($details, JSON_THROW_ON_ERROR)
        ));
    }
}
