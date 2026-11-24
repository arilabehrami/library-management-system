<?php

declare(strict_types=1);

namespace ApplicationTest\Entity;

use Application\Entity\Book;
use PHPUnit\Framework\TestCase;

final class BookTest extends TestCase
{
    public function testDecrementStockDoesNotGoBelowZero(): void
    {
        $book = new Book('Titull', 'Autor', '111', 'kat', 1);

        $book->decrementStock();
        $this->assertSame(0, $book->getStock());
        $this->assertFalse($book->isAvailable());

        $this->expectException(\DomainException::class);
        $book->decrementStock();
    }

    public function testIncrementRestoresAvailability(): void
    {
        $book = new Book('Titull', 'Autor', '111', 'kat', 0);
        $this->assertFalse($book->isAvailable());

        $book->incrementStock();
        $this->assertTrue($book->isAvailable());
    }
}