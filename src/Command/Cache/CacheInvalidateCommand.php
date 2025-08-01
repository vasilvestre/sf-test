<?php

declare(strict_types=1);

namespace App\Command\Cache;

use App\Shared\Infrastructure\Cache\CacheInvalidationManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Console command for managing cache invalidation.
 */
#[AsCommand(
    name: 'cache:invalidate:quiz',
    description: 'Invalidate specific quiz application caches'
)]
final class CacheInvalidateCommand extends Command
{
    public function __construct(
        private readonly CacheInvalidationManager $invalidationManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('type', InputArgument::OPTIONAL, 'Type of cache to invalidate (all, quiz, analytics, user, leaderboard)', 'all')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'Specific user ID to invalidate')
            ->addOption('tags', 't', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Specific cache tags to invalidate')
            ->addOption('reason', 'r', InputOption::VALUE_OPTIONAL, 'Reason for cache invalidation')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force invalidation without confirmation')
            ->setHelp('This command invalidates specific caches or cache tags.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $type = $input->getArgument('type');
        $userId = $input->getOption('user-id');
        $tags = $input->getOption('tags');
        $reason = $input->getOption('reason') ?? 'Manual invalidation via CLI';
        $force = $input->getOption('force');

        $io->title('Quiz Application Cache Invalidation');

        // Validate inputs
        $validTypes = ['all', 'quiz', 'analytics', 'user', 'leaderboard', 'tags'];
        if (!in_array($type, $validTypes)) {
            $io->error("Invalid cache type. Valid types: " . implode(', ', $validTypes));
            return Command::FAILURE;
        }

        // Show confirmation unless forced
        if (!$force && !$this->confirmInvalidation($io, $type, $userId, $tags, $reason)) {
            $io->warning('Cache invalidation cancelled.');
            return Command::SUCCESS;
        }

        $startTime = microtime(true);

        try {
            if ($type === 'tags' && !empty($tags)) {
                $success = $this->invalidationManager->invalidateByTags($tags, $reason);
                $this->displayTagInvalidationResult($io, $tags, $success, microtime(true) - $startTime);
            } else {
                $operations = $this->buildInvalidationOperations($type, $userId);
                $results = $this->invalidationManager->bulkInvalidate($operations);
                $this->displayBulkInvalidationResults($io, $results, microtime(true) - $startTime);
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(sprintf('Cache invalidation failed: %s', $e->getMessage()));
            return Command::FAILURE;
        }
    }

    private function confirmInvalidation(SymfonyStyle $io, string $type, ?string $userId, array $tags, string $reason): bool
    {
        $io->section('Invalidation Summary');
        
        $summaryData = [
            ['Type', $type],
            ['Reason', $reason],
        ];
        
        if ($userId) {
            $summaryData[] = ['User ID', $userId];
        }
        
        if (!empty($tags)) {
            $summaryData[] = ['Tags', implode(', ', $tags)];
        }
        
        $io->table(['Parameter', 'Value'], $summaryData);

        // Show impact warning
        $impact = $this->getInvalidationImpact($type, $userId, $tags);
        if ($impact['severity'] === 'high') {
            $io->warning($impact['message']);
        } else {
            $io->note($impact['message']);
        }

        return $io->confirm('Do you want to proceed with cache invalidation?', false);
    }

    private function buildInvalidationOperations(string $type, ?string $userId): array
    {
        $operations = [];

        switch ($type) {
            case 'all':
                $operations[] = ['type' => 'quiz', 'criteria' => []];
                $operations[] = ['type' => 'analytics', 'criteria' => []];
                $operations[] = ['type' => 'user', 'criteria' => []];
                $operations[] = ['type' => 'leaderboard', 'criteria' => []];
                break;

            case 'user':
                if ($userId) {
                    $operations[] = ['type' => 'user', 'criteria' => ['user_id' => $userId]];
                } else {
                    $operations[] = ['type' => 'user', 'criteria' => []];
                }
                break;

            default:
                $criteria = [];
                if ($userId && in_array($type, ['quiz', 'analytics', 'leaderboard'])) {
                    $criteria['user_id'] = $userId;
                }
                $operations[] = ['type' => $type, 'criteria' => $criteria];
                break;
        }

        return $operations;
    }

    private function displayTagInvalidationResult(SymfonyStyle $io, array $tags, bool $success, float $duration): void
    {
        if ($success) {
            $io->success(sprintf(
                'Successfully invalidated cache tags [%s] in %.3f seconds',
                implode(', ', $tags),
                $duration
            ));
        } else {
            $io->error(sprintf(
                'Failed to invalidate cache tags [%s]',
                implode(', ', $tags)
            ));
        }
    }

    private function displayBulkInvalidationResults(SymfonyStyle $io, array $results, float $duration): void
    {
        $io->section('Invalidation Results');

        $successful = 0;
        $failed = 0;
        $rows = [];

        foreach ($results as $result) {
            $status = $result['success'] ? '✅ Success' : '❌ Failed';
            $criteria = !empty($result['criteria']) ? json_encode($result['criteria']) : 'All';
            
            $rows[] = [
                ucfirst($result['type']),
                $criteria,
                $status,
                $result['error'] ?? ''
            ];

            if ($result['success']) {
                $successful++;
            } else {
                $failed++;
            }
        }

        $io->table(['Cache Type', 'Criteria', 'Status', 'Error'], $rows);

        // Summary
        $io->writeln(sprintf(
            '<info>Summary:</info> %d successful, %d failed in %.3f seconds',
            $successful,
            $failed,
            $duration
        ));

        if ($failed === 0) {
            $io->success('All cache invalidations completed successfully');
        } else {
            $io->warning(sprintf('%d cache invalidations failed', $failed));
        }
    }

    private function getInvalidationImpact(string $type, ?string $userId, array $tags): array
    {
        if ($type === 'all' || (!empty($tags) && in_array('all', $tags))) {
            return [
                'severity' => 'high',
                'message' => 'This will invalidate ALL caches and may significantly impact performance temporarily.'
            ];
        }

        if ($userId) {
            return [
                'severity' => 'low',
                'message' => sprintf('This will invalidate caches for user %s only.', $userId)
            ];
        }

        $impactMessages = [
            'quiz' => 'This will invalidate all quiz-related caches (sessions, questions, answers).',
            'analytics' => 'This will invalidate all analytics caches (metrics, dashboards, reports).',
            'user' => 'This will invalidate all user-related caches (profiles, preferences, sessions).',
            'leaderboard' => 'This will invalidate all leaderboard caches (rankings, competitive data).',
            'tags' => 'This will invalidate caches matching the specified tags.',
        ];

        return [
            'severity' => 'medium',
            'message' => $impactMessages[$type] ?? 'This will invalidate the specified cache type.'
        ];
    }
}