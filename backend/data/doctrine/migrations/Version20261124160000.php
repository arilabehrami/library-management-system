<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261124160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Track student return requests without changing physical stock';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE loans ADD returnRequestedAt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql("COMMENT ON COLUMN loans.returnRequestedAt IS '(DC2Type:datetime_immutable)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE loans DROP returnRequestedAt');
    }
}
