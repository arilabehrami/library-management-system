<?php

declare(strict_types=1);

namespace ApplicationTest\Service;

use Application\Entity\Book;
use Application\Entity\Loan;
use Application\Entity\LoanStatus;
use Application\Entity\Notification;
use Application\Entity\Penalty;
use Application\Entity\User;
use Application\Service\NotificationService;
use Application\Service\OverdueService;
use Application\Service\ReturnService;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Logging\SQLLogger;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class OverdueServiceTest extends TestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        putenv('APP_ENV=testing');
        $app = \Laminas\Mvc\Application::init(require __DIR__ . '/../../../../config/application.config.php');
        $this->em = $app->getServiceManager()->get('doctrine.entitymanager.orm_default');
        $conn = $this->em->getConnection();
        if (!str_ends_with((string) $conn->getParams()['dbname'], '_test')) {
            throw new \RuntimeException('Tests require a database name ending in _test.');
        }
        $conn->executeStatement('TRUNCATE loans, penalties, payments, notifications, system_logs, books, users RESTART IDENTITY CASCADE');
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
    private function loan(string $due): Loan
    {
        $user = new User('Student', uniqid() . '@test.com', 'x', 'student');
        $book = new Book('Book', 'Author', uniqid(), 'Test', 0);
        $loan = new Loan($user, $book, new \DateTimeImmutable($due), '2.00');
        foreach ([$user, $book, $loan] as $entity) {
            $this->em->persist($entity);
        }
        $this->em->flush();
        return $loan;
    }

    public function testOverdueProcessingDoesNotDuplicateAlerts(): void
    {
        $loan = $this->loan('2026-11-21 23:59:59');
        $service = new OverdueService($this->em);
        $now = new \DateTimeImmutable('2026-11-24 12:00:00');
        $this->assertSame(1, $service->run($now));
        $this->assertSame(0, $service->run($now));
        $this->assertSame(LoanStatus::Overdue, $loan->getStatus());
        $this->assertSame(1, $this->em->getRepository(Notification::class)->count(['type' => 'overdue']));
    }

    public function testDueDateRemainsActiveUntilNextCalendarDay(): void
    {
        $loan = $this->loan('2026-11-24 23:59:59');
        $count = (new OverdueService($this->em))->run(new \DateTimeImmutable('2026-11-24 23:59:59.999999'));
        $this->assertSame(0, $count);
        $this->assertSame(LoanStatus::Active, $loan->getStatus());
    }

    public function testConcurrentReturnAndPaymentCannotBeOverwrittenByOverdueScan(): void
    {
        $loan = $this->loan('2026-11-21 23:59:59');
        $staff = new User('Operator', 'operator@test.com', 'x', 'operator');
        $this->em->persist($staff);
        $this->em->flush();
        $loanId = $loan->getId();
        $staffId = $staff->getId();
        $now = new \DateTimeImmutable('2026-11-24 12:00:00');
        $other = new EntityManager(DriverManager::getConnection($this->em->getConnection()->getParams()), $this->em->getConfiguration());
        $callback = function () use ($other, $loanId, $staffId, $now): void {
            $actor = $other->find(User::class, $staffId);
            $returns = new ReturnService($other, new NotificationService($other));
            $returns->returnLoan($loanId, $actor, $now);
            $returns->payPenalty($loanId, $actor, $now);
        };
        $logger = new class($callback) implements SQLLogger {
            private bool $selectedLoans = false;

            public function __construct(private ?\Closure $callback)
            {
            }

            public function startQuery($sql, ?array $params = null, ?array $types = null)
            {
                $this->selectedLoans = str_starts_with($sql, 'SELECT') && str_contains($sql, 'FROM loans');
            }

            public function stopQuery()
            {
                if ($this->selectedLoans && $this->callback !== null) {
                    $callback = $this->callback;
                    $this->callback = null;
                    $this->selectedLoans = false;
                    $callback();
                }
            }
        };
        $config = $this->em->getConnection()->getConfiguration();
        $previousLogger = $config->getSQLLogger();
        $config->setSQLLogger($logger);
        try {
            $count = (new OverdueService($this->em))->run($now);
        } finally {
            $config->setSQLLogger($previousLogger);
            $other->close();
            $other->getConnection()->close();
        }
        $this->em->refresh($loan);
        $penalty = $this->em->getRepository(Penalty::class)->findOneBy(['loan' => $loan]);
        $this->assertSame(0, $count);
        $this->assertSame(LoanStatus::Closed, $loan->getStatus());
        $this->assertNotNull($loan->getReturnedAt());
        $this->assertTrue($penalty->isPaid());
        $this->assertSame(0, $this->em->getRepository(Notification::class)->count(['type' => 'overdue']));
    }
}
