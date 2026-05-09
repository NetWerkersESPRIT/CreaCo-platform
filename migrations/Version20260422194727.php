<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260422194727 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE contract_clause (id INT AUTO_INCREMENT NOT NULL, category VARCHAR(100) NOT NULL, title VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, is_active TINYINT NOT NULL, is_mandatory TINYINT NOT NULL, is_editable TINYINT NOT NULL, version INT NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE contract_template (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, is_master TINYINT NOT NULL, version INT NOT NULL, updated_at DATETIME NOT NULL, custom_fields JSON DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE idea_usage (id INT AUTO_INCREMENT NOT NULL, date_used DATETIME NOT NULL, user_id INT NOT NULL, idea_id INT NOT NULL, INDEX IDX_912C6DA5A76ED395 (user_id), INDEX IDX_912C6DA55B6FEF7D (idea_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE idea_usage ADD CONSTRAINT FK_912C6DA5A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE idea_usage ADD CONSTRAINT FK_912C6DA55B6FEF7D FOREIGN KEY (idea_id) REFERENCES idea (id)');
        $this->addSql('ALTER TABLE badge CHANGE icon icon VARCHAR(255) DEFAULT NULL, CHANGE rarity rarity VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE categorie_cours CHANGE date_de_modification date_de_modification DATETIME DEFAULT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE collab_request DROP FOREIGN KEY `FK_195F8ECF30098C8C`');
        $this->addSql('ALTER TABLE collab_request DROP FOREIGN KEY `FK_195F8ECF61220EA6`');
        $this->addSql('ALTER TABLE collab_request ADD ai_success_score INT DEFAULT NULL, ADD ai_clarity_score INT DEFAULT NULL, ADD ai_budget_realism_score INT DEFAULT NULL, ADD ai_timeline_feasibility_score INT DEFAULT NULL, ADD ai_flags JSON DEFAULT NULL, ADD ai_usage_count INT NOT NULL, ADD ai_original_content LONGTEXT DEFAULT NULL, ADD ai_rephrased_content LONGTEXT DEFAULT NULL, CHANGE budget budget NUMERIC(10, 2) DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE responded_at responded_at DATETIME DEFAULT NULL, CHANGE collaborator_id collaborator_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE collab_request ADD CONSTRAINT FK_195F8ECF30098C8C FOREIGN KEY (collaborator_id) REFERENCES collaborator (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE collab_request ADD CONSTRAINT FK_195F8ECF61220EA6 FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE collaborator CHANGE phone phone VARCHAR(50) DEFAULT NULL, CHANGE website website VARCHAR(255) DEFAULT NULL, CHANGE domain domain VARCHAR(100) DEFAULT NULL, CHANGE logo logo VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE comment CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE contract DROP FOREIGN KEY `FK_E98F285930098C8C`');
        $this->addSql('ALTER TABLE contract DROP FOREIGN KEY `FK_E98F285961220EA6`');
        $this->addSql('ALTER TABLE contract DROP FOREIGN KEY `FK_E98F2859A266EDF4`');
        $this->addSql('ALTER TABLE contract ADD docusign_envelope_id VARCHAR(255) DEFAULT NULL, CHANGE pdf_path pdf_path VARCHAR(255) DEFAULT NULL, CHANGE creator_signature_date creator_signature_date DATETIME DEFAULT NULL, CHANGE collaborator_signature_date collaborator_signature_date DATETIME DEFAULT NULL, CHANGE signature_token signature_token VARCHAR(255) DEFAULT NULL, CHANGE sent_at sent_at DATETIME DEFAULT NULL, CHANGE collaborator_id collaborator_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE contract ADD CONSTRAINT FK_E98F285930098C8C FOREIGN KEY (collaborator_id) REFERENCES collaborator (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE contract ADD CONSTRAINT FK_E98F285961220EA6 FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE contract ADD CONSTRAINT FK_E98F2859A266EDF4 FOREIGN KEY (collab_request_id) REFERENCES collab_request (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE cours CHANGE image image VARCHAR(255) DEFAULT NULL, CHANGE date_de_modification date_de_modification DATETIME DEFAULT NULL, CHANGE niveau niveau VARCHAR(20) DEFAULT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE event CHANGE meeting_link meeting_link VARCHAR(255) DEFAULT NULL, CHANGE platform platform VARCHAR(255) DEFAULT NULL, CHANGE address address VARCHAR(255) DEFAULT NULL, CHANGE contact contact VARCHAR(255) DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE image_path image_path VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE idea CHANGE last_used last_used DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE message CHANGE read_at read_at DATETIME DEFAULT NULL, CHANGE status status VARCHAR(50) DEFAULT \'visible\' NOT NULL');
        $this->addSql('ALTER TABLE mission CHANGE last_update last_update DATETIME DEFAULT NULL, CHANGE mission_date mission_date DATETIME DEFAULT NULL, CHANGE completed_at completed_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE notification CHANGE target_url target_url VARCHAR(255) DEFAULT NULL, CHANGE type type VARCHAR(50) DEFAULT \'default\' NOT NULL, CHANGE status status VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE post DROP tags, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE image_name image_name VARCHAR(255) DEFAULT NULL, CHANGE pdf_name pdf_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE ressource CHANGE url url VARCHAR(255) DEFAULT NULL, CHANGE type type VARCHAR(255) DEFAULT NULL, CHANGE date_de_creation date_de_creation DATETIME DEFAULT NULL, CHANGE date_de_modification date_de_modification DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE task CHANGE time_limit time_limit DATETIME DEFAULT NULL, CHANGE completed_at completed_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE user_badge CHANGE metadata metadata JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE user_badge RENAME INDEX idx_user_badge_user TO IDX_1C32B345A76ED395');
        $this->addSql('ALTER TABLE user_badge RENAME INDEX idx_user_badge_badge TO IDX_1C32B345F7A2C2FC');
        $this->addSql('ALTER TABLE user_cours_progress CHANGE completed_at completed_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE user_ressource_progress CHANGE opened_at opened_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE user_streak_day RENAME INDEX idx_user_streak_user TO IDX_67E282A2A76ED395');
        $this->addSql('ALTER TABLE users DROP groupid, CHANGE numtel numtel VARCHAR(20) DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE idea_usage DROP FOREIGN KEY FK_912C6DA5A76ED395');
        $this->addSql('ALTER TABLE idea_usage DROP FOREIGN KEY FK_912C6DA55B6FEF7D');
        $this->addSql('DROP TABLE contract_clause');
        $this->addSql('DROP TABLE contract_template');
        $this->addSql('DROP TABLE idea_usage');
        $this->addSql('ALTER TABLE badge CHANGE icon icon VARCHAR(255) DEFAULT \'NULL\', CHANGE rarity rarity VARCHAR(50) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE categorie_cours CHANGE date_de_modification date_de_modification DATETIME DEFAULT \'NULL\', CHANGE deleted_at deleted_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE collaborator CHANGE phone phone VARCHAR(50) DEFAULT \'NULL\', CHANGE website website VARCHAR(255) DEFAULT \'NULL\', CHANGE domain domain VARCHAR(100) DEFAULT \'NULL\', CHANGE logo logo VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE collab_request DROP FOREIGN KEY FK_195F8ECF61220EA6');
        $this->addSql('ALTER TABLE collab_request DROP FOREIGN KEY FK_195F8ECF30098C8C');
        $this->addSql('ALTER TABLE collab_request DROP ai_success_score, DROP ai_clarity_score, DROP ai_budget_realism_score, DROP ai_timeline_feasibility_score, DROP ai_flags, DROP ai_usage_count, DROP ai_original_content, DROP ai_rephrased_content, CHANGE budget budget NUMERIC(10, 2) DEFAULT \'NULL\', CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\', CHANGE responded_at responded_at DATETIME DEFAULT \'NULL\', CHANGE collaborator_id collaborator_id INT NOT NULL');
        $this->addSql('ALTER TABLE collab_request ADD CONSTRAINT `FK_195F8ECF61220EA6` FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE collab_request ADD CONSTRAINT `FK_195F8ECF30098C8C` FOREIGN KEY (collaborator_id) REFERENCES collaborator (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE comment CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE contract DROP FOREIGN KEY FK_E98F2859A266EDF4');
        $this->addSql('ALTER TABLE contract DROP FOREIGN KEY FK_E98F285961220EA6');
        $this->addSql('ALTER TABLE contract DROP FOREIGN KEY FK_E98F285930098C8C');
        $this->addSql('ALTER TABLE contract DROP docusign_envelope_id, CHANGE pdf_path pdf_path VARCHAR(255) DEFAULT \'NULL\', CHANGE creator_signature_date creator_signature_date DATETIME DEFAULT \'NULL\', CHANGE collaborator_signature_date collaborator_signature_date DATETIME DEFAULT \'NULL\', CHANGE signature_token signature_token VARCHAR(255) DEFAULT \'NULL\', CHANGE sent_at sent_at DATETIME DEFAULT \'NULL\', CHANGE collaborator_id collaborator_id INT NOT NULL');
        $this->addSql('ALTER TABLE contract ADD CONSTRAINT `FK_E98F2859A266EDF4` FOREIGN KEY (collab_request_id) REFERENCES collab_request (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE contract ADD CONSTRAINT `FK_E98F285961220EA6` FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE contract ADD CONSTRAINT `FK_E98F285930098C8C` FOREIGN KEY (collaborator_id) REFERENCES collaborator (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cours CHANGE image image VARCHAR(255) DEFAULT \'NULL\', CHANGE date_de_modification date_de_modification DATETIME DEFAULT \'NULL\', CHANGE niveau niveau VARCHAR(20) DEFAULT \'NULL\', CHANGE deleted_at deleted_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE event CHANGE created_at created_at DATETIME DEFAULT \'NULL\', CHANGE meeting_link meeting_link VARCHAR(255) DEFAULT \'NULL\', CHANGE platform platform VARCHAR(255) DEFAULT \'NULL\', CHANGE address address VARCHAR(255) DEFAULT \'NULL\', CHANGE contact contact VARCHAR(255) DEFAULT \'NULL\', CHANGE image_path image_path VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE idea CHANGE last_used last_used DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE message CHANGE read_at read_at DATETIME DEFAULT \'NULL\', CHANGE status status VARCHAR(50) DEFAULT \'\'\'visible\'\'\' NOT NULL');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE mission CHANGE last_update last_update DATETIME DEFAULT \'NULL\', CHANGE mission_date mission_date DATETIME DEFAULT \'NULL\', CHANGE completed_at completed_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE notification CHANGE target_url target_url VARCHAR(255) DEFAULT \'NULL\', CHANGE type type VARCHAR(50) DEFAULT \'\'\'default\'\'\' NOT NULL, CHANGE status status VARCHAR(20) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE post ADD tags VARCHAR(255) DEFAULT \'NULL\', CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\', CHANGE image_name image_name VARCHAR(255) DEFAULT \'NULL\', CHANGE pdf_name pdf_name VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE ressource CHANGE url url VARCHAR(255) DEFAULT \'NULL\', CHANGE type type VARCHAR(255) DEFAULT \'NULL\', CHANGE date_de_creation date_de_creation DATETIME DEFAULT \'NULL\', CHANGE date_de_modification date_de_modification DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE task CHANGE time_limit time_limit DATETIME DEFAULT \'NULL\', CHANGE completed_at completed_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE users ADD groupid INT DEFAULT NULL, CHANGE numtel numtel VARCHAR(20) DEFAULT \'NULL\', CHANGE created_at created_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE user_badge CHANGE metadata metadata LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE user_badge RENAME INDEX idx_1c32b345a76ed395 TO IDX_USER_BADGE_USER');
        $this->addSql('ALTER TABLE user_badge RENAME INDEX idx_1c32b345f7a2c2fc TO IDX_USER_BADGE_BADGE');
        $this->addSql('ALTER TABLE user_cours_progress CHANGE completed_at completed_at DATETIME DEFAULT \'NULL\', CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE user_ressource_progress CHANGE opened_at opened_at DATETIME DEFAULT \'NULL\', CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE user_streak_day RENAME INDEX idx_67e282a2a76ed395 TO IDX_USER_STREAK_USER');
    }
}
