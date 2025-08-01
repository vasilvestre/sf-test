<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration for quiz sessions table supporting advanced quiz engine.
 */
final class Version20250801140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create quiz_sessions table for advanced quiz engine with adaptive learning support';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE quiz_sessions (
            id UUID PRIMARY KEY,
            user_id UUID NOT NULL,
            questions JSON NOT NULL DEFAULT \'[]\',
            question_answers JSON NOT NULL DEFAULT \'[]\',
            current_question_index INTEGER NOT NULL DEFAULT 0,
            target_difficulty_level INTEGER NOT NULL DEFAULT 5,
            time_limit_seconds INTEGER NULL,
            adaptive_learning BOOLEAN NOT NULL DEFAULT true,
            practice_mode BOOLEAN NOT NULL DEFAULT false,
            metadata JSON NOT NULL DEFAULT \'{}\',
            started_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            completed_at TIMESTAMP(0) WITHOUT TIME ZONE NULL,
            total_time_spent DOUBLE PRECISION NULL,
            is_completed BOOLEAN NOT NULL DEFAULT false,
            adaptive_learning_data JSON NOT NULL DEFAULT \'[]\',
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
        )');

        $this->addSql('COMMENT ON COLUMN quiz_sessions.started_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN quiz_sessions.completed_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN quiz_sessions.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN quiz_sessions.updated_at IS \'(DC2Type:datetime_immutable)\'');

        // Add indexes for performance
        $this->addSql('CREATE INDEX idx_user_completion ON quiz_sessions (user_id, is_completed)');
        $this->addSql('CREATE INDEX idx_started_at ON quiz_sessions (started_at)');
        $this->addSql('CREATE INDEX idx_completed_at ON quiz_sessions (completed_at)');
        $this->addSql('CREATE INDEX idx_user_started ON quiz_sessions (user_id, started_at)');
        $this->addSql('CREATE INDEX idx_difficulty_level ON quiz_sessions (target_difficulty_level)');
        $this->addSql('CREATE INDEX idx_practice_mode ON quiz_sessions (practice_mode)');

        // Add constraints
        $this->addSql('ALTER TABLE quiz_sessions ADD CONSTRAINT check_difficulty_level 
            CHECK (target_difficulty_level >= 1 AND target_difficulty_level <= 10)');
        $this->addSql('ALTER TABLE quiz_sessions ADD CONSTRAINT check_question_index 
            CHECK (current_question_index >= 0)');
        $this->addSql('ALTER TABLE quiz_sessions ADD CONSTRAINT check_time_limit 
            CHECK (time_limit_seconds IS NULL OR time_limit_seconds > 0)');
        $this->addSql('ALTER TABLE quiz_sessions ADD CONSTRAINT check_time_spent 
            CHECK (total_time_spent IS NULL OR total_time_spent >= 0)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE quiz_sessions');
    }
}