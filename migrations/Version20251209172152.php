<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251209172152 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // First, set any invalid foreign key references to NULL
        // (in case orders table has processed_by_id values referencing old admin table)
        $this->addSql('UPDATE orders SET processed_by_id = NULL WHERE processed_by_id IS NOT NULL');
        
        // Check if columns exist before adding
        $userTable = $schema->getTable('user');
        if (!$userTable->hasColumn('email')) {
            $this->addSql('ALTER TABLE user ADD email VARCHAR(255) DEFAULT NULL');
        }
        if (!$userTable->hasColumn('full_name')) {
            $this->addSql('ALTER TABLE user ADD full_name VARCHAR(255) DEFAULT NULL');
        }
        if (!$userTable->hasColumn('created_at')) {
            $this->addSql('ALTER TABLE user ADD created_at DATETIME DEFAULT NULL');
        }
        if (!$userTable->hasColumn('updated_at')) {
            $this->addSql('ALTER TABLE user ADD updated_at DATETIME DEFAULT NULL');
        }
        
        // Drop existing foreign key if it exists, then add new one
        $ordersTable = $schema->getTable('orders');
        if ($ordersTable->hasForeignKey('FK_E52FFDEE2FFD4FD3')) {
            $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE2FFD4FD3');
        }
        
        // Add foreign key to user table
        if ($ordersTable->hasColumn('processed_by_id')) {
            $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE2FFD4FD3 FOREIGN KEY (processed_by_id) REFERENCES user (id) ON DELETE SET NULL');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE2FFD4FD3');
        $this->addSql('ALTER TABLE user DROP email, DROP full_name, DROP created_at, DROP updated_at');
    }
}
