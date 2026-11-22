<?php

namespace Application\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $email;

    #[ORM\Column(type: 'string', length: 255)]
    private string $passwordHash;

    #[ORM\Column(type: 'string', length: 20)]
    private string $role;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    public function __construct(
        string $name,
        string $email,
        string $passwordHash,
        string $role
    ) {
        $this->name = $name;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->role = $role;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int{
        return $this->id;
    }

    public function getName(): string{
        return $this->name;
    }

    public function setName(string $name): void{
        $this->name = $name;
    }

    public function getEmail(): string{
        return $this->email;
    }

    public function setEmail(string $email): void{
        $this->email = $email;
    }

    public function getPasswordHash(): string{
        return $this->passwordHash;
    }

    public function setPasswordHash(string $passwordHash): void{
        $this->passwordHash = $passwordHash;
    }

    public function getRole(): string{
        return $this->role;
    }

    public function setRole(string $role): void{
        $this->role = $role;
    }

    public function getCreatedAt(): \DateTimeImmutable{
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable{
        return $this->updatedAt;
    }

    public function updateUpdatedAt(): void{
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function delete(\DateTimeImmutable $at): void{
        $this->deletedAt = $at;
    }

    public function isDeleted(): bool{
        return $this->deletedAt !== null;
    }

    public function isStaff(): bool{
        return in_array($this->role, ['operator', 'super_admin'], true);
    }
}