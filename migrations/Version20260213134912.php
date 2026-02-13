<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260213134912 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE categorie_cours (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, date_de_creation DATETIME DEFAULT NULL, date_de_modification DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_8B2614C6C6E55B5 (nom), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE collab_request (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, budget NUMERIC(10, 2) DEFAULT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, status VARCHAR(50) NOT NULL, rejection_reason LONGTEXT DEFAULT NULL, deliverables LONGTEXT DEFAULT NULL, payment_terms LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, responded_at DATETIME DEFAULT NULL, creator_id INT DEFAULT NULL, revisor_id INT DEFAULT NULL, collaborator_id INT NOT NULL, INDEX IDX_195F8ECF61220EA6 (creator_id), INDEX IDX_195F8ECFBD3183DF (revisor_id), INDEX IDX_195F8ECF30098C8C (collaborator_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE collaborator (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, company_name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(50) DEFAULT NULL, address LONGTEXT DEFAULT NULL, website VARCHAR(255) DEFAULT NULL, domain VARCHAR(100) DEFAULT NULL, description LONGTEXT DEFAULT NULL, logo VARCHAR(255) DEFAULT NULL, is_public TINYINT NOT NULL, status VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, added_by_user_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_606D487CE7927C74 (email), INDEX IDX_606D487CCA792C6B (added_by_user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE comment (id INT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, status VARCHAR(50) NOT NULL, likes INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, post_id INT NOT NULL, user_id INT DEFAULT NULL, parent_comment_id INT DEFAULT NULL, INDEX IDX_9474526C4B89032C (post_id), INDEX IDX_9474526CA76ED395 (user_id), INDEX IDX_9474526CBF2AF943 (parent_comment_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE contract (id INT AUTO_INCREMENT NOT NULL, contract_number VARCHAR(100) NOT NULL, title VARCHAR(255) NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, amount NUMERIC(10, 2) NOT NULL, pdf_path VARCHAR(255) DEFAULT NULL, status VARCHAR(50) NOT NULL, signed_by_creator TINYINT NOT NULL, signed_by_collaborator TINYINT NOT NULL, creator_signature_date DATETIME DEFAULT NULL, collaborator_signature_date DATETIME DEFAULT NULL, terms LONGTEXT DEFAULT NULL, payment_schedule LONGTEXT DEFAULT NULL, confidentiality_clause LONGTEXT DEFAULT NULL, cancellation_terms LONGTEXT DEFAULT NULL, signature_token VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, sent_at DATETIME DEFAULT NULL, collab_request_id INT NOT NULL, creator_id INT DEFAULT NULL, collaborator_id INT NOT NULL, UNIQUE INDEX UNIQ_E98F2859AAD0FA19 (contract_number), UNIQUE INDEX UNIQ_E98F2859A266EDF4 (collab_request_id), INDEX IDX_E98F285961220EA6 (creator_id), INDEX IDX_E98F285930098C8C (collaborator_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE cours (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, image VARCHAR(255) DEFAULT NULL, date_de_creation DATETIME DEFAULT NULL, date_de_modification DATETIME DEFAULT NULL, categorie_id INT NOT NULL, INDEX IDX_FDCA8C9CBCF5E72D (categorie_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE event (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, type VARCHAR(20) NOT NULL, category VARCHAR(30) NOT NULL, date DATE NOT NULL, time TIME NOT NULL, organizer VARCHAR(255) NOT NULL, is_for_all_users TINYINT NOT NULL, meeting_link VARCHAR(255) DEFAULT NULL, platform VARCHAR(255) DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, google_maps_link VARCHAR(255) DEFAULT NULL, capacity INT DEFAULT NULL, contact VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE event_users (event_id INT NOT NULL, users_id INT NOT NULL, INDEX IDX_559814C571F7E88B (event_id), INDEX IDX_559814C567B3B43D (users_id), PRIMARY KEY (event_id, users_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE idea (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, category VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, last_used DATETIME DEFAULT NULL, creator_id INT NOT NULL, INDEX IDX_A8BCA4561220EA6 (creator_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE idea_users (idea_id INT NOT NULL, users_id INT NOT NULL, INDEX IDX_5544963C5B6FEF7D (idea_id), INDEX IDX_5544963C67B3B43D (users_id), PRIMARY KEY (idea_id, users_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE mission (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, state VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, last_update DATETIME DEFAULT NULL, implement_idea_id INT NOT NULL, assigned_by_id INT NOT NULL, INDEX IDX_9067F23CE70560BA (implement_idea_id), INDEX IDX_9067F23C6E6F1246 (assigned_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, message LONGTEXT NOT NULL, is_read TINYINT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, related_post_id INT DEFAULT NULL, INDEX IDX_BF5476CAA76ED395 (user_id), INDEX IDX_BF5476CA7490C989 (related_post_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE post (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, status VARCHAR(50) NOT NULL, refusal_reason LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, pinned TINYINT DEFAULT 0 NOT NULL, content LONGTEXT NOT NULL, tags VARCHAR(255) DEFAULT NULL, image_name VARCHAR(255) DEFAULT NULL, pdf_name VARCHAR(255) DEFAULT NULL, likes INT DEFAULT 0 NOT NULL, solution_id INT DEFAULT NULL, user_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_5A8A6C8D1C0BE183 (solution_id), INDEX IDX_5A8A6C8DA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE reservation (id INT AUTO_INCREMENT NOT NULL, reserved_at DATETIME NOT NULL, status VARCHAR(255) NOT NULL, event_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_42C8495571F7E88B (event_id), INDEX IDX_42C84955A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ressource (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, url VARCHAR(255) DEFAULT NULL, type VARCHAR(255) DEFAULT NULL, contenu LONGTEXT DEFAULT NULL, date_de_creation DATETIME DEFAULT NULL, date_de_modification DATETIME DEFAULT NULL, cours_id INT NOT NULL, INDEX IDX_939F45447ECF78B0 (cours_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE task (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, state VARCHAR(255) NOT NULL, crated_at DATETIME NOT NULL, time_tlimit DATETIME DEFAULT NULL, issued_by_id INT NOT NULL, assumed_by_id INT DEFAULT NULL, belong_to_id INT DEFAULT NULL, INDEX IDX_527EDB25784BB717 (issued_by_id), INDEX IDX_527EDB2523A6A192 (assumed_by_id), INDEX IDX_527EDB25568163B1 (belong_to_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, role VARCHAR(255) NOT NULL, groupid INT DEFAULT NULL, numtel VARCHAR(20) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE collab_request ADD CONSTRAINT FK_195F8ECF61220EA6 FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE collab_request ADD CONSTRAINT FK_195F8ECFBD3183DF FOREIGN KEY (revisor_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE collab_request ADD CONSTRAINT FK_195F8ECF30098C8C FOREIGN KEY (collaborator_id) REFERENCES collaborator (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE collaborator ADD CONSTRAINT FK_606D487CCA792C6B FOREIGN KEY (added_by_user_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C4B89032C FOREIGN KEY (post_id) REFERENCES post (id)');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CBF2AF943 FOREIGN KEY (parent_comment_id) REFERENCES comment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE contract ADD CONSTRAINT FK_E98F2859A266EDF4 FOREIGN KEY (collab_request_id) REFERENCES collab_request (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE contract ADD CONSTRAINT FK_E98F285961220EA6 FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE contract ADD CONSTRAINT FK_E98F285930098C8C FOREIGN KEY (collaborator_id) REFERENCES collaborator (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cours ADD CONSTRAINT FK_FDCA8C9CBCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie_cours (id)');
        $this->addSql('ALTER TABLE event_users ADD CONSTRAINT FK_559814C571F7E88B FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event_users ADD CONSTRAINT FK_559814C567B3B43D FOREIGN KEY (users_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE idea ADD CONSTRAINT FK_A8BCA4561220EA6 FOREIGN KEY (creator_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE idea_users ADD CONSTRAINT FK_5544963C5B6FEF7D FOREIGN KEY (idea_id) REFERENCES idea (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE idea_users ADD CONSTRAINT FK_5544963C67B3B43D FOREIGN KEY (users_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE mission ADD CONSTRAINT FK_9067F23CE70560BA FOREIGN KEY (implement_idea_id) REFERENCES idea (id)');
        $this->addSql('ALTER TABLE mission ADD CONSTRAINT FK_9067F23C6E6F1246 FOREIGN KEY (assigned_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA7490C989 FOREIGN KEY (related_post_id) REFERENCES post (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8D1C0BE183 FOREIGN KEY (solution_id) REFERENCES comment (id)');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8DA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C8495571F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE ressource ADD CONSTRAINT FK_939F45447ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id)');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25784BB717 FOREIGN KEY (issued_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB2523A6A192 FOREIGN KEY (assumed_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25568163B1 FOREIGN KEY (belong_to_id) REFERENCES mission (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE collab_request DROP FOREIGN KEY FK_195F8ECF61220EA6');
        $this->addSql('ALTER TABLE collab_request DROP FOREIGN KEY FK_195F8ECFBD3183DF');
        $this->addSql('ALTER TABLE collab_request DROP FOREIGN KEY FK_195F8ECF30098C8C');
        $this->addSql('ALTER TABLE collaborator DROP FOREIGN KEY FK_606D487CCA792C6B');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526C4B89032C');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526CA76ED395');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526CBF2AF943');
        $this->addSql('ALTER TABLE contract DROP FOREIGN KEY FK_E98F2859A266EDF4');
        $this->addSql('ALTER TABLE contract DROP FOREIGN KEY FK_E98F285961220EA6');
        $this->addSql('ALTER TABLE contract DROP FOREIGN KEY FK_E98F285930098C8C');
        $this->addSql('ALTER TABLE cours DROP FOREIGN KEY FK_FDCA8C9CBCF5E72D');
        $this->addSql('ALTER TABLE event_users DROP FOREIGN KEY FK_559814C571F7E88B');
        $this->addSql('ALTER TABLE event_users DROP FOREIGN KEY FK_559814C567B3B43D');
        $this->addSql('ALTER TABLE idea DROP FOREIGN KEY FK_A8BCA4561220EA6');
        $this->addSql('ALTER TABLE idea_users DROP FOREIGN KEY FK_5544963C5B6FEF7D');
        $this->addSql('ALTER TABLE idea_users DROP FOREIGN KEY FK_5544963C67B3B43D');
        $this->addSql('ALTER TABLE mission DROP FOREIGN KEY FK_9067F23CE70560BA');
        $this->addSql('ALTER TABLE mission DROP FOREIGN KEY FK_9067F23C6E6F1246');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA76ED395');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA7490C989');
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8D1C0BE183');
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8DA76ED395');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C8495571F7E88B');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955A76ED395');
        $this->addSql('ALTER TABLE ressource DROP FOREIGN KEY FK_939F45447ECF78B0');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB25784BB717');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB2523A6A192');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB25568163B1');
        $this->addSql('DROP TABLE categorie_cours');
        $this->addSql('DROP TABLE collab_request');
        $this->addSql('DROP TABLE collaborator');
        $this->addSql('DROP TABLE comment');
        $this->addSql('DROP TABLE contract');
        $this->addSql('DROP TABLE cours');
        $this->addSql('DROP TABLE event');
        $this->addSql('DROP TABLE event_users');
        $this->addSql('DROP TABLE idea');
        $this->addSql('DROP TABLE idea_users');
        $this->addSql('DROP TABLE mission');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE post');
        $this->addSql('DROP TABLE reservation');
        $this->addSql('DROP TABLE ressource');
        $this->addSql('DROP TABLE task');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
