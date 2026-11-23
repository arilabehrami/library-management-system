<?php

declare(strict_types=1);

namespace Application\Service;

use Application\Entity\SystemLog;
use Application\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class UserService
{
    private const MANAGEABLE_ROLES = ['student', 'operator'];

    public function __construct(
        private readonly EntityManagerInterface $em
    ) {
    }

    public function list(User $actor): array
    {
        $this->requireSuperAdmin($actor);

        return $this->em->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->where('u.deletedAt IS NULL')
            ->orderBy('u.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function create(User $actor, array $data, \DateTimeImmutable $now): User
    {
        $this->requireSuperAdmin($actor);

        $name = trim((string) ($data['name'] ?? ''));
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $password = (string) ($data['password'] ?? '');
        $role = (string) ($data['role'] ?? '');

        if ($name === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new \DomainException('Emri ose email-i nuk është i vlefshëm.');
        }
        $this->assertPassword($password);
        $this->assertRole($role);
        $this->assertEmailFree($email);

        $user = new User($name, $email, password_hash($password, PASSWORD_ARGON2ID), $role);

        return $this->em->wrapInTransaction(function () use ($actor, $user, $email, $role, $now): User {
            $this->em->persist($user);
            $this->em->flush();

            $this->log($actor, 'user.created', $user->getId(), $now, ['email' => $email, 'role' => $role]);
            $this->em->flush();

            return $user;
        });
    }

    public function update(User $actor, int $id, array $data, \DateTimeImmutable $now): User
    {
        $this->requireSuperAdmin($actor);
        $target = $this->findManageable($id);

        if (isset($data['name'])) {
            $target->setName(trim((string) $data['name']));
        }
        if (isset($data['email'])) {
            $email = strtolower(trim((string) $data['email']));
            if ($email !== $target->getEmail()) {
                $this->assertEmailFree($email);
                $target->setEmail($email);
            }
        }
        if (isset($data['role'])) {
            $this->assertRole((string) $data['role']);
            $target->setRole((string) $data['role']);
        }
        if (isset($data['password'])) {
            $this->assertPassword((string) $data['password']);
            $target->setPasswordHash(password_hash((string) $data['password'], PASSWORD_ARGON2ID));
        }

        $target->updateUpdatedAt();
        $this->log($actor, 'user.updated', $target->getId(), $now, array_keys($data));
        $this->em->flush();

        return $target;
    }

    public function delete(User $actor, int $id, \DateTimeImmutable $now): void
    {
        $this->requireSuperAdmin($actor);

        if ($actor->getId() === $id) {
            throw new \DomainException('Nuk mund ta fshish llogarinë tënde.');
        }

        $target = $this->findManageable($id);
        $target->delete($now);

        $this->log($actor, 'user.deleted', $target->getId(), $now, null);
        $this->em->flush();
    }

    private function findManageable(int $id): User
    {
        $user = $this->em->find(User::class, $id);
        if ($user === null || $user->isDeleted()) {
            throw new \DomainException('Përdoruesi nuk u gjet.');
        }
        if ($user->getRole() === 'super_admin') {
            throw new \DomainException('Llogaritë super_admin nuk menaxhohen nga ky endpoint.');
        }

        return $user;
    }

    private function assertRole(string $role): void
    {
        if (!in_array($role, self::MANAGEABLE_ROLES, true)) {
            throw new \DomainException('Roli duhet të jetë student ose operator.');
        }
    }

    private function assertPassword(string $password): void
    {
        if (strlen($password) < 8) {
            throw new \DomainException('Fjalëkalimi duhet të ketë të paktën 8 karaktere.');
        }
    }

    private function assertEmailFree(string $email): void
    {
        if ($this->em->getRepository(User::class)->findOneBy(['email' => $email]) !== null) {
            throw new \DomainException('Ekziston një llogari me këtë email.');
        }
    }

    private function requireSuperAdmin(User $actor): void
    {
        if ($actor->getRole() !== 'super_admin') {
            throw new \DomainException('Vetëm super admin mund të menaxhojë llogaritë.');
        }
    }

    private function log(User $actor, string $action, ?int $entityId, \DateTimeImmutable $now, mixed $details): void
    {
        $this->em->persist(new SystemLog(
            actor: $actor,
            actionType: $action,
            entityType: 'User',
            entityId: $entityId ?? 0,
            details: $details === null ? null : json_encode($details, JSON_THROW_ON_ERROR)
        ));
    }
}