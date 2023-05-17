<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230517071333 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX service_user_unique ON connection');
        $this->addSql('DROP INDEX service_user_service_id_unique ON connection');
        $this->addSql('ALTER TABLE connection ADD provider VARCHAR(255) NOT NULL, ADD provider_user_id VARCHAR(255) NOT NULL, DROP service, DROP user_service_id');
        $this->addSql('CREATE UNIQUE INDEX provider_user_unique ON connection (provider, user_id)');
        $this->addSql('CREATE UNIQUE INDEX provider_provider_user_unique ON connection (provider, provider_user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX provider_user_unique ON connection');
        $this->addSql('DROP INDEX provider_provider_user_unique ON connection');
        $this->addSql('ALTER TABLE connection ADD service VARCHAR(255) NOT NULL, ADD user_service_id VARCHAR(255) NOT NULL, DROP provider, DROP provider_user_id');
        $this->addSql('CREATE UNIQUE INDEX service_user_unique ON connection (service, user_id)');
        $this->addSql('CREATE UNIQUE INDEX service_user_service_id_unique ON connection (service, user_service_id)');
    }
}
