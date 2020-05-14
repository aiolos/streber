<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200511202928 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('CREATE TABLE activity_map (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, slug VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('DROP INDEX UNIQ_B5F1AFE5BF396750 ON activities');
        $this->addSql('ALTER TABLE activities ADD activity_map_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE activities ADD CONSTRAINT FK_B5F1AFE51B3BF64 FOREIGN KEY (activity_map_id) REFERENCES activity_map (id)');
        $this->addSql('CREATE INDEX IDX_B5F1AFE51B3BF64 ON activities (activity_map_id)');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE activities DROP FOREIGN KEY FK_B5F1AFE51B3BF64');
        $this->addSql('DROP TABLE activity_map');
        $this->addSql('DROP INDEX IDX_B5F1AFE51B3BF64 ON activities');
        $this->addSql('ALTER TABLE activities DROP activity_map_id');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B5F1AFE5BF396750 ON activities (id)');
    }
}
