<?php

namespace Application\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'penalties')]
#[ORM\Index(name: 'idx_penalties_paid', columns: ['paid'])]
class Penalty{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Loan::class)]
    #[ORM\JoinColumn(name: 'loan_id', referencedColumnName: 'id', nullable: false, unique: true)]
    private Loan $loan;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $amount;

    #[ORM\Column(type: 'integer')]
    private int $daysLate;

    #[ORM\Column(type: 'boolean')]
    private bool $paid = false;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $paidAt = null;

    public function __construct(
        Loan $loan,
        int $daysLate,
        string $dailyRate
    ) {
        $this->loan = $loan;
        $this->daysLate = $daysLate;
        $this->amount = bcmul((string) $daysLate, $dailyRate, 2);
    }

    public function getId(): ?int{
        return $this->id;
    }

    public function getLoan(): Loan{
        return $this->loan;
    }

    public function getAmount(): string{
        return $this->amount;
    }

    public function getDaysLate(): int{
        return $this->daysLate;
    }

    public function isPaid(): bool{
        return $this->paid;
    }

    public function getPaidAt(): ?\DateTimeImmutable{
        return $this->paidAt;
    }

    public function markPaid(\DateTimeImmutable $at): void{
        $this->paid = true;
        $this->paidAt = $at;
    }
}