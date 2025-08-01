<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Complete database schema migration for enterprise-grade quiz application
 * This migration creates all tables for User, Quiz, and Analytics domains
 */
final class Version20250801120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create complete enterprise-grade quiz application schema with User, Quiz, and Analytics domains';
    }

    public function up(Schema $schema): void
    {
        // User Domain Tables
        $this->addSql('CREATE TABLE users (
            id UUID PRIMARY KEY,
            email VARCHAR(255) UNIQUE NOT NULL,
            username VARCHAR(100) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            roles JSON NOT NULL DEFAULT \'["ROLE_STUDENT"]\',
            email_verified BOOLEAN DEFAULT FALSE,
            two_factor_enabled BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_login_at TIMESTAMP NULL
        )');

        $this->addSql('CREATE TABLE user_profiles (
            user_id UUID PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE,
            first_name VARCHAR(100),
            last_name VARCHAR(100),
            bio TEXT,
            avatar_path VARCHAR(500),
            date_of_birth DATE,
            timezone VARCHAR(50) DEFAULT \'UTC\',
            locale VARCHAR(10) DEFAULT \'en\',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');

        $this->addSql('CREATE TABLE user_preferences (
            user_id UUID PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE,
            difficulty_preference INTEGER DEFAULT 5,
            theme VARCHAR(20) DEFAULT \'light\',
            notifications_enabled BOOLEAN DEFAULT TRUE,
            auto_advance BOOLEAN DEFAULT FALSE,
            show_explanations BOOLEAN DEFAULT TRUE,
            sound_enabled BOOLEAN DEFAULT TRUE,
            preferences_json JSON
        )');

        $this->addSql('CREATE TABLE achievements (
            id UUID PRIMARY KEY,
            type VARCHAR(50) NOT NULL,
            title VARCHAR(200) NOT NULL,
            description TEXT,
            icon VARCHAR(100),
            points INTEGER DEFAULT 0,
            requirements JSON
        )');

        $this->addSql('CREATE TABLE user_achievements (
            id UUID PRIMARY KEY,
            user_id UUID REFERENCES users(id) ON DELETE CASCADE,
            achievement_id UUID REFERENCES achievements(id) ON DELETE CASCADE,
            earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            progress INTEGER DEFAULT 100,
            UNIQUE(user_id, achievement_id)
        )');

        $this->addSql('CREATE TABLE study_plans (
            id UUID PRIMARY KEY,
            user_id UUID REFERENCES users(id) ON DELETE CASCADE,
            name VARCHAR(200) NOT NULL,
            description TEXT,
            target_categories JSON,
            target_completion_date DATE,
            is_active BOOLEAN DEFAULT TRUE,
            progress INTEGER DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');

        // Quiz Domain Tables
        $this->addSql('CREATE TABLE categories (
            id UUID PRIMARY KEY,
            name VARCHAR(200) NOT NULL,
            slug VARCHAR(200) UNIQUE NOT NULL,
            description TEXT,
            icon VARCHAR(100),
            color VARCHAR(7),
            parent_id UUID REFERENCES categories(id),
            difficulty_level INTEGER DEFAULT 5,
            sort_order INTEGER DEFAULT 0,
            is_active BOOLEAN DEFAULT TRUE,
            metadata JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');

        $this->addSql('CREATE TABLE question_types (
            id VARCHAR(50) PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            scoring_strategy VARCHAR(100) NOT NULL,
            is_active BOOLEAN DEFAULT TRUE
        )');

        $this->addSql('CREATE TABLE questions (
            id UUID PRIMARY KEY,
            category_id UUID REFERENCES categories(id),
            question_type_id VARCHAR(50) REFERENCES question_types(id),
            content JSON NOT NULL,
            explanation JSON,
            difficulty_level INTEGER DEFAULT 5,
            estimated_time INTEGER DEFAULT 30,
            scoring_weight DECIMAL(5,2) DEFAULT 1.0,
            tags JSON DEFAULT \'[]\',
            metadata JSON,
            created_by UUID REFERENCES users(id),
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');

        $this->addSql('CREATE TABLE answers (
            id UUID PRIMARY KEY,
            question_id UUID REFERENCES questions(id) ON DELETE CASCADE,
            content JSON NOT NULL,
            is_correct BOOLEAN NOT NULL,
            explanation JSON,
            score_value DECIMAL(5,2) DEFAULT 1.0,
            position INTEGER DEFAULT 0,
            metadata JSON
        )');

        $this->addSql('CREATE TABLE quiz_templates (
            id UUID PRIMARY KEY,
            name VARCHAR(200) NOT NULL,
            description TEXT,
            template_type VARCHAR(50) NOT NULL,
            configuration JSON NOT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_by UUID REFERENCES users(id),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');

        $this->addSql('CREATE TABLE quizzes (
            id UUID PRIMARY KEY,
            title VARCHAR(300) NOT NULL,
            description TEXT,
            template_id UUID REFERENCES quiz_templates(id),
            category_id UUID REFERENCES categories(id),
            difficulty_range JSON,
            time_limit INTEGER,
            max_attempts INTEGER,
            passing_score INTEGER DEFAULT 70,
            configuration JSON,
            is_published BOOLEAN DEFAULT FALSE,
            created_by UUID REFERENCES users(id),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');

        $this->addSql('CREATE TABLE quiz_questions (
            quiz_id UUID REFERENCES quizzes(id) ON DELETE CASCADE,
            question_id UUID REFERENCES questions(id) ON DELETE CASCADE,
            position INTEGER NOT NULL,
            weight DECIMAL(5,2) DEFAULT 1.0,
            PRIMARY KEY (quiz_id, question_id)
        )');

        $this->addSql('CREATE TABLE quiz_attempts (
            id UUID PRIMARY KEY,
            user_id UUID REFERENCES users(id),
            quiz_id UUID REFERENCES quizzes(id),
            attempt_number INTEGER NOT NULL,
            started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            completed_at TIMESTAMP NULL,
            time_spent INTEGER,
            final_score DECIMAL(5,2),
            max_possible_score DECIMAL(5,2),
            percentage_score DECIMAL(5,2),
            status VARCHAR(20) DEFAULT \'in_progress\',
            metadata JSON,
            UNIQUE(user_id, quiz_id, attempt_number)
        )');

        $this->addSql('CREATE TABLE user_answers (
            id UUID PRIMARY KEY,
            quiz_attempt_id UUID REFERENCES quiz_attempts(id) ON DELETE CASCADE,
            question_id UUID REFERENCES questions(id),
            answer_ids JSON,
            user_input JSON,
            is_correct BOOLEAN,
            score_earned DECIMAL(5,2),
            time_spent INTEGER,
            answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');

        // Analytics Domain Tables
        $this->addSql('CREATE TABLE performance_metrics (
            id UUID PRIMARY KEY,
            user_id UUID REFERENCES users(id),
            quiz_attempt_id UUID REFERENCES quiz_attempts(id),
            category_id UUID REFERENCES categories(id),
            question_id UUID REFERENCES questions(id),
            metric_type VARCHAR(50) NOT NULL,
            value DECIMAL(10,4) NOT NULL,
            context JSON,
            recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');

        $this->addSql('CREATE TABLE daily_user_stats (
            id UUID PRIMARY KEY,
            user_id UUID REFERENCES users(id),
            date DATE NOT NULL,
            quizzes_attempted INTEGER DEFAULT 0,
            questions_answered INTEGER DEFAULT 0,
            correct_answers INTEGER DEFAULT 0,
            total_time_spent INTEGER DEFAULT 0,
            categories_practiced JSON DEFAULT \'[]\',
            performance_data JSON,
            UNIQUE(user_id, date)
        )');

        $this->addSql('CREATE TABLE category_performance (
            id UUID PRIMARY KEY,
            user_id UUID REFERENCES users(id),
            category_id UUID REFERENCES categories(id),
            total_attempts INTEGER DEFAULT 0,
            total_correct INTEGER DEFAULT 0,
            total_questions INTEGER DEFAULT 0,
            best_score DECIMAL(5,2) DEFAULT 0,
            average_score DECIMAL(5,2) DEFAULT 0,
            current_difficulty INTEGER DEFAULT 5,
            last_attempt_at TIMESTAMP,
            mastery_level VARCHAR(20) DEFAULT \'beginner\',
            UNIQUE(user_id, category_id)
        )');

        $this->addSql('CREATE TABLE leaderboards (
            id UUID PRIMARY KEY,
            category_id UUID REFERENCES categories(id),
            period VARCHAR(20) NOT NULL,
            period_start DATE NOT NULL,
            period_end DATE NOT NULL,
            rankings JSON NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(category_id, period, period_start)
        )');

        $this->addSql('CREATE TABLE learning_analytics (
            id UUID PRIMARY KEY,
            user_id UUID REFERENCES users(id),
            analysis_type VARCHAR(50) NOT NULL,
            insights JSON NOT NULL,
            recommendations JSON,
            confidence_score DECIMAL(3,2),
            generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');

        // Create Indexes for Performance
        $this->createIndexes();

        // Insert default question types
        $this->insertDefaultQuestionTypes();
    }

    public function down(Schema $schema): void
    {
        // Analytics Domain Tables
        $this->addSql('DROP TABLE IF EXISTS learning_analytics');
        $this->addSql('DROP TABLE IF EXISTS leaderboards');
        $this->addSql('DROP TABLE IF EXISTS category_performance');
        $this->addSql('DROP TABLE IF EXISTS daily_user_stats');
        $this->addSql('DROP TABLE IF EXISTS performance_metrics');

        // Quiz Domain Tables
        $this->addSql('DROP TABLE IF EXISTS user_answers');
        $this->addSql('DROP TABLE IF EXISTS quiz_attempts');
        $this->addSql('DROP TABLE IF EXISTS quiz_questions');
        $this->addSql('DROP TABLE IF EXISTS quizzes');
        $this->addSql('DROP TABLE IF EXISTS quiz_templates');
        $this->addSql('DROP TABLE IF EXISTS answers');
        $this->addSql('DROP TABLE IF EXISTS questions');
        $this->addSql('DROP TABLE IF EXISTS question_types');
        $this->addSql('DROP TABLE IF EXISTS categories');

        // User Domain Tables
        $this->addSql('DROP TABLE IF EXISTS study_plans');
        $this->addSql('DROP TABLE IF EXISTS user_achievements');
        $this->addSql('DROP TABLE IF EXISTS achievements');
        $this->addSql('DROP TABLE IF EXISTS user_preferences');
        $this->addSql('DROP TABLE IF EXISTS user_profiles');
        $this->addSql('DROP TABLE IF EXISTS users');
    }

    private function createIndexes(): void
    {
        // User domain indexes
        $this->addSql('CREATE INDEX idx_users_email ON users(email)');
        $this->addSql('CREATE INDEX idx_users_username ON users(username)');
        $this->addSql('CREATE INDEX idx_users_email_verified ON users(email_verified)');
        $this->addSql('CREATE INDEX idx_users_last_login ON users(last_login_at)');
        $this->addSql('CREATE INDEX idx_user_achievements_user_id ON user_achievements(user_id)');
        $this->addSql('CREATE INDEX idx_user_achievements_earned_at ON user_achievements(earned_at)');

        // Quiz domain indexes
        $this->addSql('CREATE INDEX idx_categories_slug ON categories(slug)');
        $this->addSql('CREATE INDEX idx_categories_parent ON categories(parent_id)');
        $this->addSql('CREATE INDEX idx_categories_difficulty ON categories(difficulty_level)');
        $this->addSql('CREATE INDEX idx_categories_active ON categories(is_active)');
        $this->addSql('CREATE INDEX idx_questions_category_id ON questions(category_id)');
        $this->addSql('CREATE INDEX idx_questions_difficulty ON questions(difficulty_level)');
        $this->addSql('CREATE INDEX idx_questions_type ON questions(question_type_id)');
        $this->addSql('CREATE INDEX idx_questions_active ON questions(is_active)');
        $this->addSql('CREATE INDEX idx_quiz_attempts_user_quiz ON quiz_attempts(user_id, quiz_id)');
        $this->addSql('CREATE INDEX idx_quiz_attempts_status ON quiz_attempts(status)');
        $this->addSql('CREATE INDEX idx_quiz_attempts_completed ON quiz_attempts(completed_at)');

        // Analytics indexes
        $this->addSql('CREATE INDEX idx_performance_metrics_user_date ON performance_metrics(user_id, recorded_at)');
        $this->addSql('CREATE INDEX idx_performance_metrics_type ON performance_metrics(metric_type)');
        $this->addSql('CREATE INDEX idx_daily_stats_user_date ON daily_user_stats(user_id, date)');
        $this->addSql('CREATE INDEX idx_category_performance_user ON category_performance(user_id)');
        $this->addSql('CREATE INDEX idx_category_performance_category ON category_performance(category_id)');
        $this->addSql('CREATE INDEX idx_category_performance_mastery ON category_performance(mastery_level)');
        $this->addSql('CREATE INDEX idx_leaderboards_category_period ON leaderboards(category_id, period)');
        $this->addSql('CREATE INDEX idx_leaderboards_period_start ON leaderboards(period_start)');
        $this->addSql('CREATE INDEX idx_learning_analytics_user ON learning_analytics(user_id)');
        $this->addSql('CREATE INDEX idx_learning_analytics_type ON learning_analytics(analysis_type)');
        $this->addSql('CREATE INDEX idx_learning_analytics_generated ON learning_analytics(generated_at)');

        // GIN indexes for JSON fields (PostgreSQL specific)
        $this->addSql('CREATE INDEX idx_questions_tags_gin ON questions USING GIN (tags)');
        $this->addSql('CREATE INDEX idx_questions_content_gin ON questions USING GIN (content)');
        $this->addSql('CREATE INDEX idx_questions_metadata_gin ON questions USING GIN (metadata)');
    }

    private function insertDefaultQuestionTypes(): void
    {
        $this->addSql("INSERT INTO question_types (id, name, description, scoring_strategy) VALUES 
            ('multiple_choice', 'Multiple Choice', 'Single correct answer from multiple options', 'binary'),
            ('multiple_select', 'Multiple Select', 'Multiple correct answers from options', 'partial_credit'),
            ('true_false', 'True/False', 'Binary true or false question', 'binary'),
            ('fill_blank', 'Fill in the Blank', 'Text input for missing words', 'exact_match'),
            ('essay', 'Essay Question', 'Long-form text response', 'manual_grading'),
            ('matching', 'Matching', 'Match items from two lists', 'partial_credit'),
            ('ordering', 'Ordering', 'Arrange items in correct sequence', 'partial_credit'),
            ('numeric', 'Numeric Answer', 'Numerical value input', 'range_match'),
            ('code', 'Code Question', 'Programming code input', 'test_cases'),
            ('drag_drop', 'Drag and Drop', 'Interactive drag and drop interface', 'position_based')
        ");
    }
}