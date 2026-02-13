<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260213204806 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE categorie_cours CHANGE date_de_creation date_de_creation DATETIME DEFAULT NULL, CHANGE date_de_modification date_de_modification DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE collab_request CHANGE budget budget NUMERIC(10, 2) DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE responded_at responded_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE collaborator CHANGE phone phone VARCHAR(50) DEFAULT NULL, CHANGE website website VARCHAR(255) DEFAULT NULL, CHANGE domain domain VARCHAR(100) DEFAULT NULL, CHANGE logo logo VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE comment CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE contract CHANGE pdf_path pdf_path VARCHAR(255) DEFAULT NULL, CHANGE creator_signature_date creator_signature_date DATETIME DEFAULT NULL, CHANGE collaborator_signature_date collaborator_signature_date DATETIME DEFAULT NULL, CHANGE signature_token signature_token VARCHAR(255) DEFAULT NULL, CHANGE sent_at sent_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE cours CHANGE image image VARCHAR(255) DEFAULT NULL, CHANGE date_de_creation date_de_creation DATETIME DEFAULT NULL, CHANGE date_de_modification date_de_modification DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE event CHANGE meeting_link meeting_link VARCHAR(255) DEFAULT NULL, CHANGE platform platform VARCHAR(255) DEFAULT NULL, CHANGE address address VARCHAR(255) DEFAULT NULL, CHANGE google_maps_link google_maps_link VARCHAR(255) DEFAULT NULL, CHANGE contact contact VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE idea CHANGE last_used last_used DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE mission CHANGE last_update last_update DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE post CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE tags tags VARCHAR(255) DEFAULT NULL, CHANGE image_name image_name VARCHAR(255) DEFAULT NULL, CHANGE pdf_name pdf_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE ressource CHANGE url url VARCHAR(255) DEFAULT NULL, CHANGE type type VARCHAR(255) DEFAULT NULL, CHANGE date_de_creation date_de_creation DATETIME DEFAULT NULL, CHANGE date_de_modification date_de_modification DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE task CHANGE time_tlimit time_tlimit DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE users CHANGE numtel numtel VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE categorie_cours CHANGE date_de_creation date_de_creation DATETIME DEFAULT \'NULL\', CHANGE date_de_modification date_de_modification DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE collaborator CHANGE phone phone VARCHAR(50) DEFAULT \'NULL\', CHANGE website website VARCHAR(255) DEFAULT \'NULL\', CHANGE domain domain VARCHAR(100) DEFAULT \'NULL\', CHANGE logo logo VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE collab_request CHANGE budget budget NUMERIC(10, 2) DEFAULT \'NULL\', CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\', CHANGE responded_at responded_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE comment CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE contract CHANGE pdf_path pdf_path VARCHAR(255) DEFAULT \'NULL\', CHANGE creator_signature_date creator_signature_date DATETIME DEFAULT \'NULL\', CHANGE collaborator_signature_date collaborator_signature_date DATETIME DEFAULT \'NULL\', CHANGE signature_token signature_token VARCHAR(255) DEFAULT \'NULL\', CHANGE sent_at sent_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE cours CHANGE image image VARCHAR(255) DEFAULT \'NULL\', CHANGE date_de_creation date_de_creation DATETIME DEFAULT \'NULL\', CHANGE date_de_modification date_de_modification DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE event CHANGE meeting_link meeting_link VARCHAR(255) DEFAULT \'NULL\', CHANGE platform platform VARCHAR(255) DEFAULT \'NULL\', CHANGE address address VARCHAR(255) DEFAULT \'NULL\', CHANGE google_maps_link google_maps_link VARCHAR(255) DEFAULT \'NULL\', CHANGE contact contact VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE idea CHANGE last_used last_used DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE mission CHANGE last_update last_update DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE post CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\', CHANGE tags tags VARCHAR(255) DEFAULT \'NULL\', CHANGE image_name image_name VARCHAR(255) DEFAULT \'NULL\', CHANGE pdf_name pdf_name VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE ressource CHANGE url url VARCHAR(255) DEFAULT \'NULL\', CHANGE type type VARCHAR(255) DEFAULT \'NULL\', CHANGE date_de_creation date_de_creation DATETIME DEFAULT \'NULL\', CHANGE date_de_modification date_de_modification DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE task CHANGE time_tlimit time_tlimit DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE users CHANGE numtel numtel VARCHAR(20) DEFAULT \'NULL\'');
    }
}
