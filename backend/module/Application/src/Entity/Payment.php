<?php

namespace Application\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'payments')]
class Payment{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Loan::class)]
    #[ORM\JoinColumn(name: 'loan_id', referencedColumnName: 'id', nullable: true)]
    private ?Loan $loan = null;

    #[ORM\ManyToOne(targetEntity: Penalty::class)]
    #[ORM\JoinColumn(name: 'penalty_id', referencedColumnName: 'id', nullable: true)]
    private ?Penalty $penalty = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $amount;

    #[ORM\Column(type: 'string', length: 50)]
    private string $paymentType;

    #[ORM\Column(type: 'string', length: 50)]
    private string $paymentMethod;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $paidAt;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'processed_by', referencedColumnName: 'id', nullable: true)]
    private ?User $processedBy = null;

    public function __construct(
        User $user,
        string $amount,
        string $paymentType,
        string $paymentMethod,
        ?Loan $loan = null,
        ?Penalty $penalty = null,
        ?User $processedBy = null
    ) {
        $this->user = $user;
        $this->amount = $amount;
        $this->paymentType = $paymentType;
        $this->paymentMethod = $paymentMethod;
        $this->loan = $loan;
        $this->penalty = $penalty;
        $this->processedBy = $processedBy;
        $this->paidAt = new \DateTimeImmutable();
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

    public function getLoan(): ?Loan{
        return $this->loan;
    }

    public function setLoan(?Loan $loan): void{
        $this->loan = $loan;
    }

    public function getPenalty(): ?Penalty{
        return $this->penalty;
    }

    public function setPenalty(?Penalty $penalty): void{
        $this->penalty = $penalty;
    }

    public function getAmount(): string{
        return $this->amount;
    }

    public function setAmount(string $amount): void{
        $this->amount = $amount;
    }

    public function getPaymentType(): string{
        return $this->paymentType;
    }

    public function setPaymentType(string $paymentType): void{
        $this->paymentType = $paymentType;
    }

    public function getPaymentMethod(): string{
        return $this->paymentMethod;
    }

    public function setPaymentMethod(string $paymentMethod): void{
        $this->paymentMethod = $paymentMethod;
    }

    public function getPaidAt(): \DateTimeImmutable{
        return $this->paidAt;
    }

    public function getProcessedBy(): ?User{
        return $this->processedBy;
    }

    public function setProcessedBy(?User $processedBy): void{
        $this->processedBy = $processedBy;
    }
}
