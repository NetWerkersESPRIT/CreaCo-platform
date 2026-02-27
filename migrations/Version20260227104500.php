<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260227104500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add badge and user_badge tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE badge (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(100) NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, icon VARCHAR(255) DEFAULT NULL, rarity VARCHAR(50) DEFAULT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX badge_code_unique (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_badge (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, badge_id INT NOT NULL, awarded_at DATETIME NOT NULL, metadata JSON DEFAULT NULL, UNIQUE INDEX user_badge_unique (user_id, badge_id), INDEX IDX_USER_BADGE_USER (user_id), INDEX IDX_USER_BADGE_BADGE (badge_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE user_badge ADD CONSTRAINT FK_USER_BADGE_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_badge ADD CONSTRAINT FK_USER_BADGE_BADGE FOREIGN KEY (badge_id) REFERENCES badge (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_badge DROP FOREIGN KEY FK_USER_BADGE_USER');
        $this->addSql('ALTER TABLE user_badge DROP FOREIGN KEY FK_USER_BADGE_BADGE');
        $this->addSql('DROP TABLE user_badge');
        $this->addSql('DROP TABLE badge');
    }
}
