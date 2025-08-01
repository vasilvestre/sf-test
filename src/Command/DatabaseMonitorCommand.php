<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:database:monitor',
    description: 'Monitor database performance and health'
)]
class DatabaseMonitorCommand extends Command
{
    public function __construct(
        private Connection $connection
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('detailed', 'd', InputOption::VALUE_NONE, 'Show detailed statistics')
            ->addOption('slow-queries', 's', InputOption::VALUE_NONE, 'Show slow queries')
            ->addOption('table-sizes', 't', InputOption::VALUE_NONE, 'Show table sizes')
            ->addOption('index-usage', 'i', InputOption::VALUE_NONE, 'Show index usage statistics')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Database Health Monitor');

        // Basic connection test
        $this->testConnection($io);

        // Database size and statistics
        $this->showDatabaseStats($io);

        // Table sizes if requested
        if ($input->getOption('table-sizes')) {
            $this->showTableSizes($io);
        }

        // Index usage if requested
        if ($input->getOption('index-usage')) {
            $this->showIndexUsage($io);
        }

        // Slow queries if requested
        if ($input->getOption('slow-queries')) {
            $this->showSlowQueries($io);
        }

        // Detailed statistics if requested
        if ($input->getOption('detailed')) {
            $this->showDetailedStats($io);
        }

        // Performance recommendations
        $this->showRecommendations($io);

        return Command::SUCCESS;
    }

    private function testConnection(SymfonyStyle $io): void
    {
        try {
            $result = $this->connection->fetchOne('SELECT 1');
            $io->success('Database connection: OK');
        } catch (\Exception $e) {
            $io->error('Database connection failed: ' . $e->getMessage());
        }
    }

    private function showDatabaseStats(SymfonyStyle $io): void
    {
        $io->section('Database Statistics');

        try {
            // Database size
            $dbSize = $this->connection->fetchOne("
                SELECT pg_size_pretty(pg_database_size(current_database())) as size
            ");

            // Connection count
            $connections = $this->connection->fetchOne("
                SELECT count(*) FROM pg_stat_activity WHERE datname = current_database()
            ");

            // Transaction statistics
            $txStats = $this->connection->fetchAssociative("
                SELECT 
                    xact_commit as commits,
                    xact_rollback as rollbacks,
                    blks_read as blocks_read,
                    blks_hit as blocks_hit,
                    round((blks_hit::float / (blks_hit + blks_read)) * 100, 2) as cache_hit_ratio
                FROM pg_stat_database 
                WHERE datname = current_database()
            ");

            $io->definitionList(
                ['Database Size' => $dbSize],
                ['Active Connections' => $connections],
                ['Committed Transactions' => number_format($txStats['commits'])],
                ['Rolled Back Transactions' => number_format($txStats['rollbacks'])],
                ['Cache Hit Ratio' => $txStats['cache_hit_ratio'] . '%']
            );

        } catch (\Exception $e) {
            $io->error('Failed to retrieve database statistics: ' . $e->getMessage());
        }
    }

    private function showTableSizes(SymfonyStyle $io): void
    {
        $io->section('Table Sizes');

        try {
            $tables = $this->connection->fetchAllAssociative("
                SELECT 
                    schemaname,
                    tablename as table_name,
                    pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) as size,
                    pg_size_pretty(pg_relation_size(schemaname||'.'||tablename)) as table_size,
                    pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename) - pg_relation_size(schemaname||'.'||tablename)) as index_size
                FROM pg_tables 
                WHERE schemaname NOT IN ('information_schema', 'pg_catalog')
                ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC
                LIMIT 20
            ");

            $io->table(
                ['Schema', 'Table', 'Total Size', 'Table Size', 'Index Size'],
                array_map(fn($row) => [
                    $row['schemaname'],
                    $row['table_name'],
                    $row['size'],
                    $row['table_size'],
                    $row['index_size']
                ], $tables)
            );

        } catch (\Exception $e) {
            $io->error('Failed to retrieve table sizes: ' . $e->getMessage());
        }
    }

    private function showIndexUsage(SymfonyStyle $io): void
    {
        $io->section('Index Usage Statistics');

        try {
            $indexes = $this->connection->fetchAllAssociative("
                SELECT 
                    schemaname,
                    tablename,
                    indexname,
                    idx_tup_read,
                    idx_tup_fetch,
                    idx_scan
                FROM pg_stat_user_indexes 
                ORDER BY idx_scan DESC
                LIMIT 20
            ");

            if (empty($indexes)) {
                $io->note('No index usage statistics available (pg_stat_statements might not be enabled)');
                return;
            }

            $io->table(
                ['Schema', 'Table', 'Index', 'Tuples Read', 'Tuples Fetched', 'Index Scans'],
                array_map(fn($row) => [
                    $row['schemaname'],
                    $row['tablename'],
                    $row['indexname'],
                    number_format($row['idx_tup_read']),
                    number_format($row['idx_tup_fetch']),
                    number_format($row['idx_scan'])
                ], $indexes)
            );

        } catch (\Exception $e) {
            $io->error('Failed to retrieve index usage: ' . $e->getMessage());
        }
    }

    private function showSlowQueries(SymfonyStyle $io): void
    {
        $io->section('Slow Queries (requires pg_stat_statements)');

        try {
            $slowQueries = $this->connection->fetchAllAssociative("
                SELECT 
                    query,
                    calls,
                    total_time,
                    mean_time,
                    rows
                FROM pg_stat_statements 
                WHERE mean_time > 100
                ORDER BY mean_time DESC 
                LIMIT 10
            ");

            if (empty($slowQueries)) {
                $io->note('No slow queries found or pg_stat_statements not available');
                return;
            }

            foreach ($slowQueries as $query) {
                $io->block([
                    sprintf('Calls: %s | Mean Time: %.2fms | Total Time: %.2fms | Rows: %s',
                        number_format($query['calls']),
                        $query['mean_time'],
                        $query['total_time'],
                        number_format($query['rows'])
                    ),
                    substr($query['query'], 0, 200) . '...'
                ], null, 'fg=yellow');
            }

        } catch (\Exception $e) {
            $io->note('pg_stat_statements extension not available or not enabled');
        }
    }

    private function showDetailedStats(SymfonyStyle $io): void
    {
        $io->section('Detailed Statistics');

        try {
            // Vacuum and analyze statistics
            $vacuumStats = $this->connection->fetchAllAssociative("
                SELECT 
                    schemaname,
                    tablename,
                    last_vacuum,
                    last_autovacuum,
                    last_analyze,
                    last_autoanalyze,
                    vacuum_count,
                    autovacuum_count,
                    analyze_count,
                    autoanalyze_count
                FROM pg_stat_user_tables 
                ORDER BY last_autovacuum DESC NULLS LAST
                LIMIT 10
            ");

            $io->text('Recent Vacuum/Analyze Activity:');
            $io->table(
                ['Schema', 'Table', 'Last Vacuum', 'Last Analyze', 'Vacuum Count', 'Analyze Count'],
                array_map(fn($row) => [
                    $row['schemaname'],
                    $row['tablename'],
                    $row['last_autovacuum'] ?? 'Never',
                    $row['last_autoanalyze'] ?? 'Never',
                    $row['vacuum_count'] + $row['autovacuum_count'],
                    $row['analyze_count'] + $row['autoanalyze_count']
                ], $vacuumStats)
            );

        } catch (\Exception $e) {
            $io->error('Failed to retrieve detailed statistics: ' . $e->getMessage());
        }
    }

    private function showRecommendations(SymfonyStyle $io): void
    {
        $io->section('Performance Recommendations');

        $recommendations = [];

        try {
            // Check cache hit ratio
            $cacheHitRatio = $this->connection->fetchOne("
                SELECT round((blks_hit::float / (blks_hit + blks_read)) * 100, 2) as ratio
                FROM pg_stat_database 
                WHERE datname = current_database()
            ");

            if ($cacheHitRatio < 95) {
                $recommendations[] = sprintf(
                    'Cache hit ratio is %.2f%% (should be >95%%). Consider increasing shared_buffers.',
                    $cacheHitRatio
                );
            }

            // Check for unused indexes
            $unusedIndexes = $this->connection->fetchOne("
                SELECT count(*) 
                FROM pg_stat_user_indexes 
                WHERE idx_scan = 0
            ");

            if ($unusedIndexes > 0) {
                $recommendations[] = sprintf(
                    '%d unused indexes found. Consider dropping them to improve write performance.',
                    $unusedIndexes
                );
            }

            // Check for missing indexes on foreign keys
            $missingFkIndexes = $this->connection->fetchOne("
                SELECT count(*)
                FROM (
                    SELECT DISTINCT c.conrelid, a.attname
                    FROM pg_constraint c
                    JOIN pg_attribute a ON a.attrelid = c.conrelid AND a.attnum = ANY(c.conkey)
                    WHERE c.contype = 'f'
                    AND NOT EXISTS (
                        SELECT 1 FROM pg_index i 
                        WHERE i.indrelid = c.conrelid 
                        AND a.attnum = ANY(i.indkey)
                    )
                ) missing_fk_indexes
            ");

            if ($missingFkIndexes > 0) {
                $recommendations[] = sprintf(
                    '%d foreign key columns without indexes found. Consider adding indexes for better join performance.',
                    $missingFkIndexes
                );
            }

        } catch (\Exception $e) {
            $recommendations[] = 'Could not analyze for recommendations: ' . $e->getMessage();
        }

        if (empty($recommendations)) {
            $io->success('No immediate performance recommendations. Database appears to be well-optimized.');
        } else {
            foreach ($recommendations as $recommendation) {
                $io->warning($recommendation);
            }
        }
    }
}