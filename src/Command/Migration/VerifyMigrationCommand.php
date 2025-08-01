<?php

declare(strict_types=1);

namespace App\Command\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:migrate:verify',
    description: 'Verify data integrity after migration'
)]
class VerifyMigrationCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Connection $connection
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Verifying Migration Data Integrity');

        $errors = [];
        $warnings = [];

        // Verify User Domain
        $io->section('Verifying User Domain');
        $this->verifyUserDomain($errors, $warnings, $io);

        // Verify Quiz Domain
        $io->section('Verifying Quiz Domain');
        $this->verifyQuizDomain($errors, $warnings, $io);

        // Verify Analytics Domain
        $io->section('Verifying Analytics Domain');
        $this->verifyAnalyticsDomain($errors, $warnings, $io);

        // Verify Relationships
        $io->section('Verifying Relationships');
        $this->verifyRelationships($errors, $warnings, $io);

        // Report results
        $this->reportResults($errors, $warnings, $io);

        return empty($errors) ? Command::SUCCESS : Command::FAILURE;
    }

    private function verifyUserDomain(array &$errors, array &$warnings, SymfonyStyle $io): void
    {
        // Check users table
        $userCount = $this->connection->fetchOne('SELECT COUNT(*) FROM users');
        $io->info(sprintf('Found %d users', $userCount));

        // Check for users without profiles
        $usersWithoutProfiles = $this->connection->fetchOne('
            SELECT COUNT(*) FROM users u 
            LEFT JOIN user_profiles up ON u.id = up.user_id 
            WHERE up.user_id IS NULL
        ');
        
        if ($usersWithoutProfiles > 0) {
            $warnings[] = sprintf('%d users without profiles', $usersWithoutProfiles);
        }

        // Check for users without preferences
        $usersWithoutPreferences = $this->connection->fetchOne('
            SELECT COUNT(*) FROM users u 
            LEFT JOIN user_preferences up ON u.id = up.user_id 
            WHERE up.user_id IS NULL
        ');
        
        if ($usersWithoutPreferences > 0) {
            $warnings[] = sprintf('%d users without preferences', $usersWithoutPreferences);
        }

        // Check achievements
        $achievementCount = $this->connection->fetchOne('SELECT COUNT(*) FROM achievements');
        $userAchievementCount = $this->connection->fetchOne('SELECT COUNT(*) FROM user_achievements');
        $io->info(sprintf('Found %d achievements and %d user achievements', $achievementCount, $userAchievementCount));
    }

    private function verifyQuizDomain(array &$errors, array &$warnings, SymfonyStyle $io): void
    {
        // Check categories
        $categoryCount = $this->connection->fetchOne('SELECT COUNT(*) FROM categories');
        $io->info(sprintf('Found %d categories', $categoryCount));

        // Check for duplicate category slugs
        $duplicateSlugs = $this->connection->fetchOne('
            SELECT COUNT(*) FROM (
                SELECT slug FROM categories GROUP BY slug HAVING COUNT(*) > 1
            ) as duplicates
        ');
        
        if ($duplicateSlugs > 0) {
            $errors[] = sprintf('%d duplicate category slugs found', $duplicateSlugs);
        }

        // Check questions
        $questionCount = $this->connection->fetchOne('SELECT COUNT(*) FROM questions');
        $answerCount = $this->connection->fetchOne('SELECT COUNT(*) FROM answers');
        $io->info(sprintf('Found %d questions with %d answers', $questionCount, $answerCount));

        // Check for questions without answers
        $questionsWithoutAnswers = $this->connection->fetchOne('
            SELECT COUNT(*) FROM questions q 
            LEFT JOIN answers a ON q.id = a.question_id 
            WHERE a.question_id IS NULL
        ');
        
        if ($questionsWithoutAnswers > 0) {
            $errors[] = sprintf('%d questions without answers', $questionsWithoutAnswers);
        }

        // Check for questions without correct answers
        $questionsWithoutCorrectAnswers = $this->connection->fetchOne('
            SELECT COUNT(*) FROM questions q 
            WHERE NOT EXISTS (
                SELECT 1 FROM answers a 
                WHERE a.question_id = q.id AND a.is_correct = true
            )
        ');
        
        if ($questionsWithoutCorrectAnswers > 0) {
            $warnings[] = sprintf('%d questions without correct answers', $questionsWithoutCorrectAnswers);
        }

        // Check quiz attempts
        $attemptCount = $this->connection->fetchOne('SELECT COUNT(*) FROM quiz_attempts');
        $userAnswerCount = $this->connection->fetchOne('SELECT COUNT(*) FROM user_answers');
        $io->info(sprintf('Found %d quiz attempts with %d user answers', $attemptCount, $userAnswerCount));
    }

    private function verifyAnalyticsDomain(array &$errors, array &$warnings, SymfonyStyle $io): void
    {
        // Check performance metrics
        $metricCount = $this->connection->fetchOne('SELECT COUNT(*) FROM performance_metrics');
        $io->info(sprintf('Found %d performance metrics', $metricCount));

        // Check daily user stats
        $dailyStatsCount = $this->connection->fetchOne('SELECT COUNT(*) FROM daily_user_stats');
        $io->info(sprintf('Found %d daily user stats records', $dailyStatsCount));

        // Check category performance
        $categoryPerformanceCount = $this->connection->fetchOne('SELECT COUNT(*) FROM category_performance');
        $io->info(sprintf('Found %d category performance records', $categoryPerformanceCount));

        // Check for orphaned analytics data
        $orphanedMetrics = $this->connection->fetchOne('
            SELECT COUNT(*) FROM performance_metrics pm 
            LEFT JOIN users u ON pm.user_id = u.id 
            WHERE u.id IS NULL
        ');
        
        if ($orphanedMetrics > 0) {
            $errors[] = sprintf('%d orphaned performance metrics', $orphanedMetrics);
        }
    }

    private function verifyRelationships(array &$errors, array &$warnings, SymfonyStyle $io): void
    {
        // Check foreign key constraints are working
        $checks = [
            [
                'description' => 'User profiles referencing non-existent users',
                'query' => 'SELECT COUNT(*) FROM user_profiles up LEFT JOIN users u ON up.user_id = u.id WHERE u.id IS NULL'
            ],
            [
                'description' => 'Questions referencing non-existent categories',
                'query' => 'SELECT COUNT(*) FROM questions q LEFT JOIN categories c ON q.category_id = c.id WHERE c.id IS NULL'
            ],
            [
                'description' => 'Answers referencing non-existent questions',
                'query' => 'SELECT COUNT(*) FROM answers a LEFT JOIN questions q ON a.question_id = q.id WHERE q.id IS NULL'
            ],
            [
                'description' => 'Quiz attempts referencing non-existent users',
                'query' => 'SELECT COUNT(*) FROM quiz_attempts qa LEFT JOIN users u ON qa.user_id = u.id WHERE u.id IS NULL'
            ],
            [
                'description' => 'User answers referencing non-existent quiz attempts',
                'query' => 'SELECT COUNT(*) FROM user_answers ua LEFT JOIN quiz_attempts qa ON ua.quiz_attempt_id = qa.id WHERE qa.id IS NULL'
            ]
        ];

        foreach ($checks as $check) {
            $count = $this->connection->fetchOne($check['query']);
            if ($count > 0) {
                $errors[] = sprintf('%s: %d', $check['description'], $count);
            } else {
                $io->comment(sprintf('✓ %s: OK', $check['description']));
            }
        }
    }

    private function reportResults(array $errors, array $warnings, SymfonyStyle $io): void
    {
        if (!empty($warnings)) {
            $io->section('Warnings');
            foreach ($warnings as $warning) {
                $io->warning($warning);
            }
        }

        if (!empty($errors)) {
            $io->section('Errors');
            foreach ($errors as $error) {
                $io->error($error);
            }
            $io->error('Migration verification failed!');
        } else {
            $io->success('Migration verification completed successfully!');
            
            // Show summary statistics
            $this->showSummaryStatistics($io);
        }
    }

    private function showSummaryStatistics(SymfonyStyle $io): void
    {
        $io->section('Migration Summary');
        
        $stats = [
            'Users' => $this->connection->fetchOne('SELECT COUNT(*) FROM users'),
            'Categories' => $this->connection->fetchOne('SELECT COUNT(*) FROM categories'),
            'Questions' => $this->connection->fetchOne('SELECT COUNT(*) FROM questions'),
            'Answers' => $this->connection->fetchOne('SELECT COUNT(*) FROM answers'),
            'Quiz Attempts' => $this->connection->fetchOne('SELECT COUNT(*) FROM quiz_attempts'),
            'User Answers' => $this->connection->fetchOne('SELECT COUNT(*) FROM user_answers'),
            'Performance Metrics' => $this->connection->fetchOne('SELECT COUNT(*) FROM performance_metrics'),
            'Daily Stats' => $this->connection->fetchOne('SELECT COUNT(*) FROM daily_user_stats'),
            'Category Performance' => $this->connection->fetchOne('SELECT COUNT(*) FROM category_performance'),
        ];

        $io->table(['Entity', 'Count'], array_map(fn($k, $v) => [$k, $v], array_keys($stats), array_values($stats)));
    }
}