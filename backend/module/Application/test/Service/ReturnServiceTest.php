<?php

declare(strict_types=1);

namespace ApplicationTest\Service;

use Application\Entity\Book;
use Application\Entity\Loan;
use Application\Entity\LoanStatus;
use Application\Entity\Penalty;
use Application\Entity\User;
use Application\Service\LoanService;
use Application\Service\NotificationService;
use Application\Service\ReturnService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class ReturnServiceTest extends TestCase
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

    protected function tearDown(): void
    {
        if (isset($this->em)) {
            $this->em->close();
            $this->em->getConnection()->close();
        }
        parent::tearDown();
    }
    private function returns(): ReturnService
    {
        return new ReturnService($this->em, new NotificationService($this->em));
    }

    private function loans(): LoanService
    {
        return new LoanService($this->em, new NotificationService($this->em));
    }

    private function staff(): User
    {
        $u = new User('Operator', 'op@test.com', 'x', 'operator');
        $this->em->persist($u);
        $this->em->flush();

        return $u;
    }

    private function student(): User
    {
        $u = new User('Student', 's@test.com', 'x', 'student');
        $this->em->persist($u);
        $this->em->flush();

        return $u;
    }

    private function borrowOn(User $student, string $date): Loan
    {
        $book = new Book('Libri', 'Autori', uniqid(), 'kat', 2);
        $this->em->persist($book);
        $this->em->flush();

        return $this->loans()->borrow($student, $book->getId(), new \DateTimeImmutable($date));
    }

    public function testReturnOnDueDateHasNoPenalty(): void
    {
        $loan = $this->borrowOn($this->student(), '2026-11-22 10:00:00');

        $returned = $this->returns()->returnLoan($loan->getId(), $this->staff(), new \DateTimeImmutable('2026-12-06 10:00:00'));

        $this->assertSame(LoanStatus::Closed, $returned->getStatus());
        $this->assertSame(0, $this->em->getRepository(Penalty::class)->count([]));
    }

    public function testReturnOneDayAfterDueCreatesPenalty(): void
    {
        $loan = $this->borrowOn($this->student(), '2026-11-22 10:00:00');

        $returned = $this->returns()->returnLoan($loan->getId(), $this->staff(), new \DateTimeImmutable('2026-12-07 10:00:00'));

        $this->assertSame(LoanStatus::AwaitingPayment, $returned->getStatus());
        $penalty = $this->em->getRepository(Penalty::class)->findOneBy(['loan' => $loan]);
        $this->assertSame(1, $penalty->getDaysLate());
        $this->assertSame('1.00', $penalty->getAmount());
    }

    public function testPenaltyIsDaysTimesRate(): void
    {
        $loan = $this->borrowOn($this->student(), '2026-11-22 10:00:00');

        $this->returns()->returnLoan($loan->getId(), $this->staff(), new \DateTimeImmutable('2026-12-10 10:00:00'));

        $penalty = $this->em->getRepository(Penalty::class)->findOneBy(['loan' => $loan]);
        $this->assertSame(4, $penalty->getDaysLate());
        $this->assertSame('4.00', $penalty->getAmount());
    }

    public function testReturnRestoresStock(): void
    {
        $student = $this->student();
        $book = new Book('Libri', 'Autori', '555', 'kat', 2);
        $this->em->persist($book);
        $this->em->flush();

        $loan = $this->loans()->borrow($student, $book->getId(), new \DateTimeImmutable('2026-11-22 10:00:00'));
        $this->assertSame(1, $book->getStock());

        $this->returns()->returnLoan($loan->getId(), $this->staff(), new \DateTimeImmutable('2026-11-23 10:00:00'));
        $this->assertSame(2, $book->getStock());
    }

    public function testPayingPenaltyClosesLoan(): void
    {
        $student = $this->student();
        $loan = $this->borrowOn($student, '2026-11-22 10:00:00');
        $this->returns()->returnLoan($loan->getId(), $this->staff(), new \DateTimeImmutable('2026-12-10 10:00:00'));

        $this->returns()->payPenalty($loan->getId(), $student, new \DateTimeImmutable('2026-12-10 10:05:00'));

        $this->assertSame(LoanStatus::Closed, $loan->getStatus());
        $penalty = $this->em->getRepository(Penalty::class)->findOneBy(['loan' => $loan]);
        $this->assertTrue($penalty->isPaid());
    }

    public function testOverdueReturnRestoresStockBeforePayment(): void
    {
        $loan = $this->borrowOn($this->student(), '2026-11-22 10:00:00');
        $this->returns()->returnLoan($loan->getId(), $this->staff(), new \DateTimeImmutable('2026-12-10 10:00:00'));
        $this->assertSame(2, $loan->getBook()->getStock());
        $this->assertSame(LoanStatus::AwaitingPayment, $loan->getStatus());
    }

    public function testReturnCannotRestoreStockTwice(): void
    {
        $loan = $this->borrowOn($this->student(), '2026-11-22 10:00:00');
        $staff = $this->staff();
        $now = new \DateTimeImmutable('2026-11-23 10:00:00');
        $this->returns()->returnLoan($loan->getId(), $staff, $now);
        $this->expectException(\DomainException::class);
        $this->returns()->returnLoan($loan->getId(), $staff, $now);
    }

    public function testOtherStudentCannotPayPenalty(): void
    {
        $loan = $this->borrowOn($this->student(), '2026-11-22 10:00:00');
        $now = new \DateTimeImmutable('2026-12-10 10:00:00');
        $this->returns()->returnLoan($loan->getId(), $this->staff(), $now);
        $other = new User('Other', 'other@test.com', 'x', 'student');
        $this->em->persist($other);
        $this->em->flush();
        $this->expectException(\DomainException::class);
        $this->returns()->payPenalty($loan->getId(), $other, $now);
    }

    public function testZeroPenaltyRateClosesOverdueReturnWithoutDebt(): void
    {
        $this->em->getConnection()->executeStatement("UPDATE system_settings SET settingvalue = '0.00' WHERE settingkey = 'penalty_rate'");
        $loan = $this->borrowOn($this->student(), '2026-11-22 10:00:00');
        $this->returns()->returnLoan($loan->getId(), $this->staff(), new \DateTimeImmutable('2026-12-10 10:00:00'));
        $this->assertSame(LoanStatus::Closed, $loan->getStatus());
        $this->assertSame(0, $this->em->getRepository(Penalty::class)->count([]));
    }

    public function testPenaltyCannotBePaidTwice(): void
    {
        $student = $this->student();
        $loan = $this->borrowOn($student, '2026-11-22 10:00:00');
        $now = new \DateTimeImmutable('2026-12-10 10:00:00');
        $this->returns()->returnLoan($loan->getId(), $this->staff(), $now);
        $this->returns()->payPenalty($loan->getId(), $student, $now);
        $this->expectException(\DomainException::class);
        $this->returns()->payPenalty($loan->getId(), $student, $now);
    }

    public function testStudentCanRequestReturnWithoutRestoringStock(): void
    {
        $student = $this->student();
        $loan = $this->borrowOn($student, '2026-11-22 10:00:00');
        $this->staff();
        $admin = new User('Admin', 'admin@test.com', 'x', 'super_admin');
        $this->em->persist($admin);
        $this->em->flush();
        $now = new \DateTimeImmutable('2026-11-23 10:00:00');
        $this->returns()->requestReturn($loan->getId(), $student, $now);

        $this->assertSame($now, $loan->getReturnRequestedAt());
        $this->assertNull($loan->getReturnedAt());
        $this->assertSame(LoanStatus::Active, $loan->getStatus());
        $this->assertSame(1, $loan->getBook()->getStock());
        $this->assertSame(0, $this->em->getRepository(Penalty::class)->count([]));
        $this->assertSame(2, $this->em->getRepository(\Application\Entity\Notification::class)->count(['type' => 'return_requested']));
        $this->assertSame(1, $this->em->getRepository(\Application\Entity\SystemLog::class)->count(['actionType' => 'loan.return_requested']));
    }

    public function testOtherStudentCannotRequestReturn(): void
    {
        $loan = $this->borrowOn($this->student(), '2026-11-22 10:00:00');
        $other = new User('Other', 'other@test.com', 'x', 'student');
        $this->em->persist($other);
        $this->em->flush();
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Nuk ke leje');
        $this->returns()->requestReturn($loan->getId(), $other, new \DateTimeImmutable());
    }

    public function testStaffCannotSubmitStudentReturnRequest(): void
    {
        $loan = $this->borrowOn($this->student(), '2026-11-22 10:00:00');
        $staff = $this->staff();
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Nuk ke leje');
        $this->returns()->requestReturn($loan->getId(), $staff, new \DateTimeImmutable());
    }

    public function testDuplicateReturnRequestIsRejected(): void
    {
        $student = $this->student();
        $loan = $this->borrowOn($student, '2026-11-22 10:00:00');
        $this->returns()->requestReturn($loan->getId(), $student, new \DateTimeImmutable('2026-11-23 10:00:00'));
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('dërguar tashmë');
        $this->returns()->requestReturn($loan->getId(), $student, new \DateTimeImmutable('2026-11-23 11:00:00'));
    }

    public function testReturnRequestDoesNotStopLateDays(): void
    {
        $student = $this->student();
        $loan = $this->borrowOn($student, '2026-11-22 10:00:00');
        $requested = new \DateTimeImmutable('2026-12-05 10:00:00');
        $this->returns()->requestReturn($loan->getId(), $student, $requested);
        $this->returns()->returnLoan($loan->getId(), $this->staff(), new \DateTimeImmutable('2026-12-09 10:00:00'));
        $penalty = $this->em->getRepository(Penalty::class)->findOneBy(['loan' => $loan]);
        $this->assertSame(3, $penalty->getDaysLate());
        $this->assertSame('3.00', $penalty->getAmount());
        $this->assertSame(2, $loan->getBook()->getStock());
        $this->assertSame(LoanStatus::AwaitingPayment, $loan->getStatus());
        $this->assertSame($requested->format(DATE_ATOM), $loan->getReturnRequestedAt()->format(DATE_ATOM));
    }

    public function testClosedLoanCannotReceiveReturnRequest(): void
    {
        $student = $this->student();
        $loan = $this->borrowOn($student, '2026-11-22 10:00:00');
        $this->returns()->returnLoan($loan->getId(), $this->staff(), new \DateTimeImmutable('2026-11-23 10:00:00'));
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('kthyer tashmë');
        $this->returns()->requestReturn($loan->getId(), $student, new \DateTimeImmutable('2026-11-24 10:00:00'));
    }

    public function testStudentCannotReturnBook(): void
    {
        $student = $this->student();
        $loan = $this->borrowOn($student, '2026-11-22 10:00:00');

        $this->expectException(\DomainException::class);
        $this->returns()->returnLoan($loan->getId(), $student, new \DateTimeImmutable('2026-11-23 10:00:00'));
    }
}