<?php

namespace Application\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'books')]
class Book{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(type: 'string', length: 255)]
    private string $author;

    #[ORM\Column(type: 'string', length: 50, unique: true)]
    private string $isbn;

    #[ORM\Column(type: 'string', length: 100)]
    private string $category;

    #[ORM\Column(type: 'integer')]
    private int $stock;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    public function __construct(
        string $title,
        string $author,
        string $isbn,
        string $category,
        int $stock
    ) {
        $this->title = $title;
        $this->author = $author;
        $this->isbn = $isbn;
        $this->category = $category;
        $this->setStock($stock);
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int{
        return $this->id;
    }

    public function getTitle(): string{
        return $this->title;
    }

    public function setTitle(string $title): void{
        $this->title = $title;
    }

    public function getAuthor(): string{
        return $this->author;
    }

    public function setAuthor(string $author): void{
        $this->author = $author;
    }

    public function getIsbn(): string{
        return $this->isbn;
    }

    public function setIsbn(string $isbn): void{
        $this->isbn = $isbn;
    }

    public function getCategory(): string{
        return $this->category;
    }

    public function setCategory(string $category): void{
        $this->category = $category;
    }

    public function getStock(): int{
        return $this->stock;
    }

    public function setStock(int $stock): void{
        if ($stock < 0) {
            throw new \DomainException('Stock nuk mund të jetë negativ.');
        }
        $this->stock = $stock;
    }

    public function decrementStock(): void{
        if ($this->stock <= 0) {
            throw new \DomainException('Libri nuk ka kopje në stok.');
        }
        $this->stock--;
    }

    public function incrementStock(): void{
        $this->stock++;
    }

    public function isAvailable(): bool{
        return $this->stock > 0;
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
}