<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251223094755 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER INDEX idx_dadd4a251e27f6bf RENAME TO idx_answer_question_id');
        $this->addSql('CREATE INDEX idx_category_name ON category (name)');
        $this->addSql('ALTER INDEX idx_b6f7494e12469de2 RENAME TO idx_question_category_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER INDEX idx_question_category_id RENAME TO idx_b6f7494e12469de2');
        $this->addSql('ALTER INDEX idx_answer_question_id RENAME TO idx_dadd4a251e27f6bf');
        $this->addSql('DROP INDEX idx_category_name');
    }
}
