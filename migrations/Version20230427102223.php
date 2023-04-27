<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230427102223 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE connection DROP FOREIGN KEY FK_29F77366ED5CA9E6');
        $this->addSql('DROP TABLE service');
        $this->addSql('DROP INDEX IDX_29F77366ED5CA9E6 ON connection');
        $this->addSql('ALTER TABLE connection ADD service VARCHAR(255) NOT NULL, DROP service_id');
        $this->addSql('CREATE UNIQUE INDEX service_user_unique ON connection (service, user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE service (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(125) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, route_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, icon_url VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_SERVICE (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('DROP INDEX service_user_unique ON connection');
        $this->addSql('ALTER TABLE connection ADD service_id INT NOT NULL, DROP service');
        $this->addSql('ALTER TABLE connection ADD CONSTRAINT FK_29F77366ED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_29F77366ED5CA9E6 ON connection (service_id)');
    }
}
