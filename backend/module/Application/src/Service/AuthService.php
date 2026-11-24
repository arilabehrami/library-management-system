<?php

declare(strict_types=1);

namespace Application\Service;

use Application\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

final class AuthService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly string $secret,
        private readonly int $ttl
    ) {
    }

    public function login(string $email, string $password, \DateTimeImmutable $now): array
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => strtolower(trim($email))]);

        if ($user === null || $user->isDeleted() || !password_verify($password, $user->getPasswordHash())) {
            throw new \DomainException('Email ose fjalëkalim i gabuar.');
        }

        $iat = $now->getTimestamp();
        $payload = [
            'sub'  => $user->getId(),
            'role' => $user->getRole(),
            'iat'  => $iat,
            'exp'  => $iat + $this->ttl,
        ];

        return [
            'token'      => JWT::encode($payload, $this->secret, 'HS256'),
            'expires_in' => $this->ttl,
            'user'       => $this->present($user),
        ];
    }

    public function userFromToken(string $token): User
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));
        } catch (\UnexpectedValueException $e) {
            throw new \DomainException('Token i pavlefshëm ose i skaduar.');
        }

        $user = $this->em->find(User::class, (int) $decoded->sub);
        if ($user === null || $user->isDeleted()) {
            throw new \DomainException('Token i pavlefshëm ose i skaduar.');
        }

        return $user;
    }

    public function present(User $user): array
    {
        return [
            'id'    => $user->getId(),
            'name'  => $user->getName(),
            'email' => $user->getEmail(),
            'role'  => $user->getRole(),
        ];
    }
}