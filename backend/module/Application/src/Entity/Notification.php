<?php

namespace Application\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'notifications')]
class Notification{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private User $user;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(type: 'text')]
    private string $message;

    #[ORM\Column(type: 'string', length: 50)]
    private string $type;

    #[ORM\Column(type: 'boolean')]
    private bool $isRead;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        User $user,
        string $title,
        string $message,
        string $type,
        bool $isRead = false
    ) {
        $this->user = $user;
        $this->title = $title;
        $this->message = $message;
        $this->type = $type;
        $this->isRead = $isRead;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int{
        return $this->id;
    }

    public function getUser(): User{
        return $this->user;
    }

    public function setUser(User $user): void{
        $this->user = $user;
    }

    public function getTitle(): string{
        return $this->title;
    }

    public function setTitle(string $title): void{
        $this->title = $title;
    }

    public function getMessage(): string{
        return $this->message;
    }

    public function setMessage(string $message): void{
        $this->message = $message;
    }

    public function getType(): string{
        return $this->type;
    }

    public function setType(string $type): void{
        $this->type = $type;
    }

    public function isRead(): bool{
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): void{
        $this->isRead = $isRead;
    }

    public function getCreatedAt(): \DateTimeImmutable{
        return $this->createdAt;
    }
}
