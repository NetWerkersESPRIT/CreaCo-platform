<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260511000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add help_ticket table for support requests and admin responses';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE help_ticket (id INT AUTO_INCREMENT NOT NULL, creator_id INT NOT NULL, course_id INT DEFAULT NULL, subject VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, status VARCHAR(50) NOT NULL, priority VARCHAR(50) NOT NULL, admin_response LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_1E9E3A5E5C1D9F8B (creator_id), INDEX IDX_1E9E3A5E1A8CA6F (course_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE help_ticket ADD CONSTRAINT FK_1E9E3A5E5C1D9F8B FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE help_ticket ADD CONSTRAINT FK_1E9E3A5E1A8CA6F FOREIGN KEY (course_id) REFERENCES cours (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE help_ticket DROP FOREIGN KEY FK_1E9E3A5E1A8CA6F');
        $this->addSql('ALTER TABLE help_ticket DROP FOREIGN KEY FK_1E9E3A5E5C1D9F8B');
        $this->addSql('DROP TABLE help_ticket');
    }
}
