<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210825170418 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE request ADD game_id INT DEFAULT NULL, ADD type VARCHAR(255) NOT NULL, DROP friend, DROP game');
        $this->addSql('ALTER TABLE request ADD CONSTRAINT FK_3B978F9FE48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
        $this->addSql('CREATE INDEX IDX_3B978F9FE48FD905 ON request (game_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE request DROP FOREIGN KEY FK_3B978F9FE48FD905');
        $this->addSql('DROP INDEX IDX_3B978F9FE48FD905 ON request');
        $this->addSql('ALTER TABLE request ADD friend TINYINT(1) NOT NULL, ADD game TINYINT(1) NOT NULL, DROP game_id, DROP type');
    }
}
