<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261122091858 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
         
        $this->addSql('CREATE SEQUENCE books_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE loans_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE notifications_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE payments_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE penalties_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE system_logs_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE system_settings_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE users_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE books (id INT NOT NULL, title VARCHAR(255) NOT NULL, author VARCHAR(255) NOT NULL, isbn VARCHAR(50) NOT NULL, category VARCHAR(100) NOT NULL, stock INT NOT NULL, createdAt TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updatedAt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, deletedAt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4A1B2A92CC1CF4E6 ON books (isbn)');
        $this->addSql('COMMENT ON COLUMN books.createdAt IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN books.updatedAt IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN books.deletedAt IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE loans (id INT NOT NULL, user_id INT NOT NULL, book_id INT NOT NULL, borrowedAt TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, dueAt TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, returnedAt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, status VARCHAR(30) NOT NULL, borrowFeeAmount NUMERIC(10, 2) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_82C24DBCA76ED395 ON loans (user_id)');
        $this->addSql('CREATE INDEX IDX_82C24DBC16A2B381 ON loans (book_id)');
        $this->addSql('CREATE INDEX idx_loans_user_status ON loans (user_id, status)');
        $this->addSql('COMMENT ON COLUMN loans.borrowedAt IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN loans.dueAt IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN loans.returnedAt IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE notifications (id INT NOT NULL, user_id INT NOT NULL, title VARCHAR(255) NOT NULL, message TEXT NOT NULL, type VARCHAR(50) NOT NULL, isRead BOOLEAN NOT NULL, createdAt TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6000B0D3A76ED395 ON notifications (user_id)');
        $this->addSql('COMMENT ON COLUMN notifications.createdAt IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE payments (id INT NOT NULL, user_id INT NOT NULL, loan_id INT DEFAULT NULL, penalty_id INT DEFAULT NULL, processed_by INT DEFAULT NULL, amount NUMERIC(10, 2) NOT NULL, paymentType VARCHAR(50) NOT NULL, paymentMethod VARCHAR(50) NOT NULL, paidAt TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_65D29B32A76ED395 ON payments (user_id)');
        $this->addSql('CREATE INDEX IDX_65D29B32CE73868F ON payments (loan_id)');
        $this->addSql('CREATE INDEX IDX_65D29B3217C4A6C7 ON payments (penalty_id)');
        $this->addSql('CREATE INDEX IDX_65D29B32888A646A ON payments (processed_by)');
        $this->addSql('COMMENT ON COLUMN payments.paidAt IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE penalties (id INT NOT NULL, loan_id INT NOT NULL, amount NUMERIC(10, 2) NOT NULL, daysLate INT NOT NULL, paid BOOLEAN NOT NULL, paidAt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B89F70AFCE73868F ON penalties (loan_id)');
        $this->addSql('CREATE INDEX idx_penalties_paid ON penalties (paid)');
        $this->addSql('COMMENT ON COLUMN penalties.paidAt IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE system_logs (id INT NOT NULL, actor_id INT NOT NULL, actionType VARCHAR(100) NOT NULL, entityType VARCHAR(100) NOT NULL, entityId INT NOT NULL, timestamp TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, details TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_E14375AA10DAF24A ON system_logs (actor_id)');
        $this->addSql('COMMENT ON COLUMN system_logs.timestamp IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE system_settings (id INT NOT NULL, settingKey VARCHAR(255) NOT NULL, settingValue TEXT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8CAF11473AA9950B ON system_settings (settingKey)');
        $this->addSql('CREATE TABLE users (id INT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, passwordHash VARCHAR(255) NOT NULL, role VARCHAR(20) NOT NULL, createdAt TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updatedAt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, deletedAt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql('COMMENT ON COLUMN users.createdAt IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN users.updatedAt IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN users.deletedAt IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE loans ADD CONSTRAINT FK_82C24DBCA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE loans ADD CONSTRAINT FK_82C24DBC16A2B381 FOREIGN KEY (book_id) REFERENCES books (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT FK_6000B0D3A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE payments ADD CONSTRAINT FK_65D29B32A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE payments ADD CONSTRAINT FK_65D29B32CE73868F FOREIGN KEY (loan_id) REFERENCES loans (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE payments ADD CONSTRAINT FK_65D29B3217C4A6C7 FOREIGN KEY (penalty_id) REFERENCES penalties (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE payments ADD CONSTRAINT FK_65D29B32888A646A FOREIGN KEY (processed_by) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE penalties ADD CONSTRAINT FK_B89F70AFCE73868F FOREIGN KEY (loan_id) REFERENCES loans (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE system_logs ADD CONSTRAINT FK_E14375AA10DAF24A FOREIGN KEY (actor_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
         
        $this->addSql('DROP SEQUENCE books_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE loans_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE notifications_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE payments_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE penalties_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE system_logs_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE system_settings_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE users_id_seq CASCADE');
        $this->addSql('ALTER TABLE loans DROP CONSTRAINT FK_82C24DBCA76ED395');
        $this->addSql('ALTER TABLE loans DROP CONSTRAINT FK_82C24DBC16A2B381');
        $this->addSql('ALTER TABLE notifications DROP CONSTRAINT FK_6000B0D3A76ED395');
        $this->addSql('ALTER TABLE payments DROP CONSTRAINT FK_65D29B32A76ED395');
        $this->addSql('ALTER TABLE payments DROP CONSTRAINT FK_65D29B32CE73868F');
        $this->addSql('ALTER TABLE payments DROP CONSTRAINT FK_65D29B3217C4A6C7');
        $this->addSql('ALTER TABLE payments DROP CONSTRAINT FK_65D29B32888A646A');
        $this->addSql('ALTER TABLE penalties DROP CONSTRAINT FK_B89F70AFCE73868F');
        $this->addSql('ALTER TABLE system_logs DROP CONSTRAINT FK_E14375AA10DAF24A');
        $this->addSql('DROP TABLE books');
        $this->addSql('DROP TABLE loans');
        $this->addSql('DROP TABLE notifications');
        $this->addSql('DROP TABLE payments');
        $this->addSql('DROP TABLE penalties');
        $this->addSql('DROP TABLE system_logs');
        $this->addSql('DROP TABLE system_settings');
        $this->addSql('DROP TABLE users');
    }
}
