<?php

declare(strict_types=1);

namespace ApplicationTest\Service;

use Application\Entity\Book;
use Application\Entity\Penalty;
use Application\Entity\User;
use Application\Service\LoanService;
use Application\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class LoanServiceTest extends TestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        putenv('APP_ENV=testing');
        $_ENV['APP_ENV'] = 'testing';

        $app = \Laminas\Mvc\Application::init(require __DIR__ . '/../../../../config/application.config.php');
        $this->em = $app->getServiceManager()->get('doctrine.entitymanager.orm_default');

        $conn = $this->em->getConnection();
        if (!str_ends_with((string) $conn->getParams()['dbname'], '_test')) {
            throw new \RuntimeException('Tests require a database name ending in _test.');
        }
        $conn->executeStatement(
            'TRUNCATE loans, penalties, payments, notifications, system_logs, books, users RESTART IDENTITY CASCADE'
        );
        $conn->executeStatement("UPDATE system_settings SET settingvalue = '2.00' WHERE settingkey = 'borrow_fee'");
        $conn->executeStatement("UPDATE system_settings SET settingvalue = '14' WHERE settingkey = 'borrowing_period'");
        $conn->executeStatement("UPDATE system_settings SET settingvalue = '3' WHERE settingkey = 'borrowing_limit'");
        $conn->executeStatement("UPDATE system_settings SET settingvalue = '1.00' WHERE settingkey = 'penalty_rate'");
    }

    private function service(): LoanService
    {
        return new LoanService($this->em, new NotificationService($this->em));
    }

    private function student(string $email = 's@test.com'): User
    {
        $u = new User('Student', $email, 'x', 'student');
        $this->em->persist($u);
        $this->em->flush();

        return $u;
    }

    private function book(int $stock, string $isbn = '111'): Book
    {
        $b = new Book('Libri', 'Autori', $isbn, 'kat', $stock);
        $this->em->persist($b);
        $this->em->flush();

        return $b;
    }

    private function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2026-11-22 10:00:00');
    }

    public function testLastCopyCanBeBorrowedOnceThenIsUnavailable(): void
    {
        $book = $this->book(1);
        $a = $this->student('a@test.com');
        $b = $this->student('b@test.com');

        $this->service()->borrow($a, $book->getId(), $this->now());

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('UNAVAILABLE:');
        $this->service()->borrow($b, $book->getId(), $this->now());
    }

    public function testBorrowingLimitIsEnforced(): void
    {
        $student = $this->student();
        for ($i = 1; $i <= 3; $i++) {
            $this->service()->borrow($student, $this->book(5, (string) $i)->getId(), $this->now());
        }

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('limitin');
        $this->service()->borrow($student, $this->book(5, '4')->getId(), $this->now());
    }

    public function testUnpaidPenaltyBlocksBorrowing(): void
    {
        $student = $this->student();
        $loan = $this->service()->borrow($student, $this->book(2)->getId(), $this->now());

        $loan->markReturned(new \DateTimeImmutable('2026-12-05 10:00:00'), true);
        $this->em->persist(new Penalty($loan, 3, '1.00'));
        $this->em->flush();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('penalitete');
        $this->service()->borrow($student, $this->book(2, '9')->getId(), $this->now());
    }

    public function testUnreturnedOverdueBookBlocksBorrowing(): void
    {
        $student = $this->student();
        $this->service()->borrow($student, $this->book(2)->getId(), $this->now());
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('penalitete');
        $this->service()->borrow($student, $this->book(2, 'new')->getId(), new \DateTimeImmutable('2026-12-07 00:00:00'));
    }

    public function testConfiguredLimitIsUsed(): void
    {
        $this->em->getConnection()->executeStatement("UPDATE system_settings SET settingvalue = '1' WHERE settingkey = 'borrowing_limit'");
        $student = $this->student();
        $this->service()->borrow($student, $this->book(2)->getId(), $this->now());
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('limitin');
        $this->service()->borrow($student, $this->book(2, 'next')->getId(), $this->now());
    }

    public function testDueDateIsEndOfDayAfterBorrowingPeriod(): void
    {
        $student = $this->student();
        $loan = $this->service()->borrow($student, $this->book(2)->getId(), $this->now());

        $this->assertSame('2026-12-06 23:59:59', $loan->getDueAt()->format('Y-m-d H:i:s'));
    }
}