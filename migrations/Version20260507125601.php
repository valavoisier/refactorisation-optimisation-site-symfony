<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260507125601 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // password : ajout avec default temporaire pour les lignes existantes
        $this->addSql('ALTER TABLE "user" ADD password VARCHAR(255) NOT NULL DEFAULT \'\'');
        $this->addSql('ALTER TABLE "user" ALTER COLUMN password DROP DEFAULT');

        // roles : ajout avec default temporaire
        $this->addSql('ALTER TABLE "user" ADD roles JSON NOT NULL DEFAULT \'[]\'');
        $this->addSql('ALTER TABLE "user" ALTER COLUMN roles DROP DEFAULT');

        // blocked : nouvelle colonne (pas un rename d'admin — concept différent)
        $this->addSql('ALTER TABLE "user" ADD blocked BOOLEAN NOT NULL DEFAULT false');
        $this->addSql('ALTER TABLE "user" ALTER COLUMN blocked DROP DEFAULT');

        // admin supprimé (remplacé par ROLE_ADMIN dans roles)
        $this->addSql('ALTER TABLE "user" DROP COLUMN admin');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE "user" DROP password');
        $this->addSql('ALTER TABLE "user" DROP roles');
        $this->addSql('ALTER TABLE "user" DROP blocked');
        $this->addSql('ALTER TABLE "user" ADD admin BOOLEAN NOT NULL DEFAULT false');
    }
}
