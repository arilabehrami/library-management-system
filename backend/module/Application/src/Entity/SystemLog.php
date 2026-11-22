<?php

namespace Application\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'system_logs')]
class SystemLog{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'actor_id', referencedColumnName: 'id', nullable: false)]
    private User $actor;

    #[ORM\Column(type: 'string', length: 100)]
    private string $actionType;

    #[ORM\Column(type: 'string', length: 100)]
    private string $entityType;

    #[ORM\Column(type: 'integer')]
    private int $entityId;

    #[ORM\Column(name: 'timestamp', type: 'datetime_immutable')]
    private \DateTimeImmutable $timestamp;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $details = null;

    public function __construct(
        User $actor,
        string $actionType,
        string $entityType,
        int $entityId,
        ?string $details = null
    ) {
        $this->actor = $actor;
        $this->actionType = $actionType;
        $this->entityType = $entityType;
        $this->entityId = $entityId;
        $this->timestamp = new \DateTimeImmutable();
        $this->details = $details;
    }

    public function getId(): ?int{
        return $this->id;
    }

    public function getActor(): User{
        return $this->actor;
    }

    public function setActor(User $actor): void{
        $this->actor = $actor;
    }

    public function getActionType(): string{
        return $this->actionType;
    }

    public function setActionType(string $actionType): void{
        $this->actionType = $actionType;
    }

    public function getEntityType(): string{
        return $this->entityType;
    }

    public function setEntityType(string $entityType): void{
        $this->entityType = $entityType;
    }

    public function getEntityId(): int{
        return $this->entityId;
    }

    public function setEntityId(int $entityId): void{
        $this->entityId = $entityId;
    }

    public function getTimestamp(): \DateTimeImmutable{
        return $this->timestamp;
    }

    public function getDetails(): ?string{
        return $this->details;
    }

    public function setDetails(?string $details): void{
        $this->details = $details;
    }
}
