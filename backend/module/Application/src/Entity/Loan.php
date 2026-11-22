<?php

namespace Application\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'loans')]
#[ORM\Index(name: 'idx_loans_user_status', columns: ['user_id', 'status'])]
class Loan{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Book::class)]
    #[ORM\JoinColumn(name: 'book_id', referencedColumnName: 'id', nullable: false)]
    private Book $book;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $borrowedAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $dueAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $returnedAt = null;

    #[ORM\Column(type: 'string', length: 30, enumType: LoanStatus::class)]
    private LoanStatus $status;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $borrowFeeAmount;

    public function __construct(
        User $user,
        Book $book,
        \DateTimeImmutable $dueAt,
        string $borrowFeeAmount
    ) {
        $this->user = $user;
        $this->book = $book;
        $this->borrowedAt = new \DateTimeImmutable();
        $this->dueAt = $dueAt;
        $this->status = LoanStatus::Active;
        $this->borrowFeeAmount = $borrowFeeAmount;
    }

    public function getId(): ?int{
        return $this->id;
    }

    public function getUser(): User{
        return $this->user;
    }

    public function getBook(): Book{
        return $this->book;
    }

    public function getBorrowedAt(): \DateTimeImmutable{
        return $this->borrowedAt;
    }

    public function getDueAt(): \DateTimeImmutable{
        return $this->dueAt;
    }

    public function getReturnedAt(): ?\DateTimeImmutable{
        return $this->returnedAt;
    }

    public function getStatus(): LoanStatus{
        return $this->status;
    }

    public function getBorrowFeeAmount(): string{
        return $this->borrowFeeAmount;
    }

    public function markReturned(\DateTimeImmutable $at, bool $hasPenalty): void{
        $this->returnedAt = $at;
        $this->status = $hasPenalty ? LoanStatus::AwaitingPayment : LoanStatus::Closed;
    }

    public function close(): void{
        $this->status = LoanStatus::Closed;
    }
}