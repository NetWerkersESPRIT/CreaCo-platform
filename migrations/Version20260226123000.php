<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260226123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create user_streak_day table for daily reading streaks';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_streak_day (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, day DATE NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX user_date_unique (user_id, day), INDEX IDX_USER_STREAK_USER (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE user_streak_day ADD CONSTRAINT FK_USER_STREAK_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_streak_day DROP FOREIGN KEY FK_USER_STREAK_USER');
        $this->addSql('DROP TABLE user_streak_day');
    }
}
