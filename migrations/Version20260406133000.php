<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260406133000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user verification and google oauth fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD is_verified TINYINT(1) NOT NULL DEFAULT 0, ADD verification_token VARCHAR(255) DEFAULT NULL, ADD google_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649C48F8383 ON user (google_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_8D93D649C48F8383 ON user');
        $this->addSql('ALTER TABLE user DROP is_verified, DROP verification_token, DROP google_id');
    }
}

