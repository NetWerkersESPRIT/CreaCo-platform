<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260513172448 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY `question_ibfk_1`');
        $this->addSql('ALTER TABLE quiz DROP FOREIGN KEY `quiz_ibfk_1`');
        $this->addSql('ALTER TABLE quiz_result DROP FOREIGN KEY `quiz_result_ibfk_1`');
        $this->addSql('ALTER TABLE quiz_result DROP FOREIGN KEY `quiz_result_ibfk_2`');
        $this->addSql('DROP TABLE question');
        $this->addSql('DROP TABLE quiz');
        $this->addSql('DROP TABLE quiz_result');
        $this->addSql('DROP TABLE user_resource_completion');
        $this->addSql('ALTER TABLE badge CHANGE icon icon VARCHAR(255) DEFAULT NULL, CHANGE rarity rarity VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE categorie_cours CHANGE date_de_modification date_de_modification DATETIME DEFAULT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE collab_request CHANGE budget budget NUMERIC(10, 2) DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE responded_at responded_at DATETIME DEFAULT NULL, CHANGE ai_flags ai_flags JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE collaborator CHANGE phone phone VARCHAR(50) DEFAULT NULL, CHANGE website website VARCHAR(255) DEFAULT NULL, CHANGE domain domain VARCHAR(100) DEFAULT NULL, CHANGE logo logo VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE comment CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE comment_like ADD CONSTRAINT FK_8A55E25FF8697D13 FOREIGN KEY (comment_id) REFERENCES comment (id)');
        $this->addSql('ALTER TABLE comment_like ADD CONSTRAINT FK_8A55E25FA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_8A55E25FF8697D13 ON comment_like (comment_id)');
        $this->addSql('CREATE INDEX IDX_8A55E25FA76ED395 ON comment_like (user_id)');
        $this->addSql('ALTER TABLE contract CHANGE pdf_path pdf_path VARCHAR(255) DEFAULT NULL, CHANGE creator_signature_date creator_signature_date DATETIME DEFAULT NULL, CHANGE collaborator_signature_date collaborator_signature_date DATETIME DEFAULT NULL, CHANGE signature_token signature_token VARCHAR(255) DEFAULT NULL, CHANGE sent_at sent_at DATETIME DEFAULT NULL, CHANGE docusign_envelope_id docusign_envelope_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE contract_template CHANGE custom_fields custom_fields JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE cours DROP likes, DROP dislikes, CHANGE image image VARCHAR(255) DEFAULT NULL, CHANGE date_de_modification date_de_modification DATETIME DEFAULT NULL, CHANGE niveau niveau VARCHAR(20) DEFAULT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE event CHANGE meeting_link meeting_link VARCHAR(255) DEFAULT NULL, CHANGE platform platform VARCHAR(255) DEFAULT NULL, CHANGE address address VARCHAR(255) DEFAULT NULL, CHANGE contact contact VARCHAR(255) DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE image_path image_path VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE idea CHANGE last_used last_used DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE message CHANGE read_at read_at DATETIME DEFAULT NULL, CHANGE status status VARCHAR(50) DEFAULT \'visible\' NOT NULL');
        $this->addSql('ALTER TABLE mission CHANGE last_update last_update DATETIME DEFAULT NULL, CHANGE mission_date mission_date DATETIME DEFAULT NULL, CHANGE completed_at completed_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE notification CHANGE target_url target_url VARCHAR(255) DEFAULT NULL, CHANGE type type VARCHAR(50) DEFAULT \'default\' NOT NULL, CHANGE status status VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE post CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE image_name image_name VARCHAR(255) DEFAULT NULL, CHANGE pdf_name pdf_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE ressource CHANGE url url VARCHAR(255) DEFAULT NULL, CHANGE type type VARCHAR(255) DEFAULT NULL, CHANGE date_de_creation date_de_creation DATETIME DEFAULT NULL, CHANGE date_de_modification date_de_modification DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE task CHANGE time_limit time_limit DATETIME DEFAULT NULL, CHANGE completed_at completed_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE user_badge CHANGE metadata metadata JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE user_badge RENAME INDEX idx_user_badge_user TO IDX_1C32B345A76ED395');
        $this->addSql('ALTER TABLE user_badge RENAME INDEX idx_user_badge_badge TO IDX_1C32B345F7A2C2FC');
        $this->addSql('ALTER TABLE user_cours_progress DROP progress, DROP completed_ressources, CHANGE completed_at completed_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE user_ressource_progress CHANGE opened_at opened_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE user_streak_day RENAME INDEX idx_user_streak_user TO IDX_67E282A2A76ED395');
        $this->addSql('ALTER TABLE users ADD image LONGTEXT DEFAULT NULL, CHANGE numtel numtel VARCHAR(20) DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE question (id INT AUTO_INCREMENT NOT NULL, quiz_id INT NOT NULL, question_text TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, options LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`, correct_answer_index INT NOT NULL, INDEX idx_question_quiz_id (quiz_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE quiz (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, resource_id INT NOT NULL, created_date DATETIME NOT NULL, INDEX idx_quiz_resource_id (resource_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE quiz_result (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, quiz_id INT NOT NULL, score DOUBLE PRECISION NOT NULL, submitted_date VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, answers TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, INDEX idx_quiz_result_quiz_id (quiz_id), UNIQUE INDEX unique_user_quiz (user_id, quiz_id), INDEX idx_quiz_result_user_id (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE user_resource_completion (user_id INT NOT NULL, ressource_id INT NOT NULL, cours_id INT DEFAULT NULL, created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, PRIMARY KEY (user_id, ressource_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE question ADD CONSTRAINT `question_ibfk_1` FOREIGN KEY (quiz_id) REFERENCES quiz (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE quiz ADD CONSTRAINT `quiz_ibfk_1` FOREIGN KEY (resource_id) REFERENCES ressource (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE quiz_result ADD CONSTRAINT `quiz_result_ibfk_1` FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE quiz_result ADD CONSTRAINT `quiz_result_ibfk_2` FOREIGN KEY (quiz_id) REFERENCES quiz (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE badge CHANGE icon icon VARCHAR(255) DEFAULT \'NULL\', CHANGE rarity rarity VARCHAR(50) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE categorie_cours CHANGE date_de_modification date_de_modification DATETIME DEFAULT \'NULL\', CHANGE deleted_at deleted_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE collaborator CHANGE phone phone VARCHAR(50) DEFAULT \'NULL\', CHANGE website website VARCHAR(255) DEFAULT \'NULL\', CHANGE domain domain VARCHAR(100) DEFAULT \'NULL\', CHANGE logo logo VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE collab_request CHANGE budget budget NUMERIC(10, 2) DEFAULT \'NULL\', CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\', CHANGE responded_at responded_at DATETIME DEFAULT \'NULL\', CHANGE ai_flags ai_flags LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE comment CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE comment_like DROP FOREIGN KEY FK_8A55E25FF8697D13');
        $this->addSql('ALTER TABLE comment_like DROP FOREIGN KEY FK_8A55E25FA76ED395');
        $this->addSql('DROP INDEX IDX_8A55E25FF8697D13 ON comment_like');
        $this->addSql('DROP INDEX IDX_8A55E25FA76ED395 ON comment_like');
        $this->addSql('ALTER TABLE contract CHANGE pdf_path pdf_path VARCHAR(255) DEFAULT \'NULL\', CHANGE creator_signature_date creator_signature_date DATETIME DEFAULT \'NULL\', CHANGE collaborator_signature_date collaborator_signature_date DATETIME DEFAULT \'NULL\', CHANGE signature_token signature_token VARCHAR(255) DEFAULT \'NULL\', CHANGE docusign_envelope_id docusign_envelope_id VARCHAR(255) DEFAULT \'NULL\', CHANGE sent_at sent_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE contract_template CHANGE custom_fields custom_fields LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE cours ADD likes INT DEFAULT NULL, ADD dislikes INT DEFAULT NULL, CHANGE image image VARCHAR(255) DEFAULT \'NULL\', CHANGE date_de_modification date_de_modification DATETIME DEFAULT \'NULL\', CHANGE niveau niveau VARCHAR(20) DEFAULT \'NULL\', CHANGE deleted_at deleted_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE event CHANGE created_at created_at DATETIME DEFAULT \'NULL\', CHANGE meeting_link meeting_link VARCHAR(255) DEFAULT \'NULL\', CHANGE platform platform VARCHAR(255) DEFAULT \'NULL\', CHANGE address address VARCHAR(255) DEFAULT \'NULL\', CHANGE contact contact VARCHAR(255) DEFAULT \'NULL\', CHANGE image_path image_path VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE idea CHANGE last_used last_used DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE message CHANGE read_at read_at DATETIME DEFAULT \'NULL\', CHANGE status status VARCHAR(50) DEFAULT \'\'\'visible\'\'\' NOT NULL');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE mission CHANGE last_update last_update DATETIME DEFAULT \'NULL\', CHANGE mission_date mission_date DATETIME DEFAULT \'NULL\', CHANGE completed_at completed_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE notification CHANGE target_url target_url VARCHAR(255) DEFAULT \'NULL\', CHANGE type type VARCHAR(50) DEFAULT \'\'\'default\'\'\' NOT NULL, CHANGE status status VARCHAR(20) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE post CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\', CHANGE image_name image_name VARCHAR(255) DEFAULT \'NULL\', CHANGE pdf_name pdf_name VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE ressource CHANGE url url VARCHAR(255) DEFAULT \'NULL\', CHANGE type type VARCHAR(255) DEFAULT \'NULL\', CHANGE date_de_creation date_de_creation DATETIME DEFAULT \'NULL\', CHANGE date_de_modification date_de_modification DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE task CHANGE time_limit time_limit DATETIME DEFAULT \'NULL\', CHANGE completed_at completed_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE users DROP image, CHANGE numtel numtel VARCHAR(20) DEFAULT \'NULL\', CHANGE created_at created_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE user_badge CHANGE metadata metadata LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE user_badge RENAME INDEX idx_1c32b345a76ed395 TO IDX_USER_BADGE_USER');
        $this->addSql('ALTER TABLE user_badge RENAME INDEX idx_1c32b345f7a2c2fc TO IDX_USER_BADGE_BADGE');
        $this->addSql('ALTER TABLE user_cours_progress ADD progress DOUBLE PRECISION DEFAULT \'0\', ADD completed_ressources INT DEFAULT 0, CHANGE completed_at completed_at DATETIME DEFAULT \'NULL\', CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE user_ressource_progress CHANGE opened_at opened_at DATETIME DEFAULT \'NULL\', CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE user_streak_day RENAME INDEX idx_67e282a2a76ed395 TO IDX_USER_STREAK_USER');
    }
}
