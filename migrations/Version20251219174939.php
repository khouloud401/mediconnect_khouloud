<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251219174939 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE nurse (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(20) NOT NULL, shift VARCHAR(50) DEFAULT NULL, team_number INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE appointment CHANGE motif motif VARCHAR(1000) DEFAULT NULL, CHANGE notes notes VARCHAR(2000) DEFAULT NULL');
        $this->addSql('ALTER TABLE doctor CHANGE description description VARCHAR(1000) DEFAULT NULL, CHANGE experience experience VARCHAR(255) DEFAULT NULL, CHANGE horaires horaires VARCHAR(500) DEFAULT NULL, CHANGE photo photo VARCHAR(255) DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE patient CHANGE ville ville VARCHAR(255) DEFAULT NULL, CHANGE adresse adresse VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE prescription CHANGE pdf_path pdf_path VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE specialty CHANGE description description VARCHAR(1000) DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE nurse');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE appointment CHANGE motif motif VARCHAR(1000) DEFAULT \'NULL\', CHANGE notes notes VARCHAR(2000) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE doctor CHANGE description description VARCHAR(1000) DEFAULT \'NULL\', CHANGE experience experience VARCHAR(255) DEFAULT \'NULL\', CHANGE horaires horaires VARCHAR(500) DEFAULT \'NULL\', CHANGE photo photo VARCHAR(255) DEFAULT \'NULL\', CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE patient CHANGE ville ville VARCHAR(255) DEFAULT \'NULL\', CHANGE adresse adresse VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE prescription CHANGE pdf_path pdf_path VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE specialty CHANGE description description VARCHAR(1000) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE `user` CHANGE roles roles LONGTEXT NOT NULL COLLATE `utf8mb4_bin`');
    }
}
