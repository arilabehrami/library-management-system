<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261122092000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO system_settings (id, settingkey, settingvalue) VALUES
        (nextval('system_settings_id_seq'), 'borrow_fee', '2.00'),
        (nextval('system_settings_id_seq'), 'penalty_rate', '1.00'),
        (nextval('system_settings_id_seq'), 'borrowing_period', '14'),
        (nextval('system_settings_id_seq'), 'borrowing_limit', '3')");
    }
    
    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM system_settings WHERE settingkey IN ('borrow_fee', 'penalty_rate', 'borrowing_period', 'borrowing_limit')");
    }
}
