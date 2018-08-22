<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180822200550 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE posts DROP FOREIGN KEY FK_885DBAFA5E5E6949');
        $this->addSql('RENAME TABLE acitivityGoups TO activityGroups');
        $this->addSql('ALTER TABLE posts ADD CONSTRAINT FK_885DBAFA5E5E6949 FOREIGN KEY (activity_group_id) REFERENCES activityGroups (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE posts DROP FOREIGN KEY FK_885DBAFA5E5E6949');
        $this->addSql('RENAME TABLE activityGroups TO acitivityGoups');
        $this->addSql('ALTER TABLE posts ADD CONSTRAINT FK_885DBAFA5E5E6949 FOREIGN KEY (activity_group_id) REFERENCES acitivityGoups (id)');
    }
}
