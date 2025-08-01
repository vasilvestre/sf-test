<?php

declare(strict_types=1);

namespace App\Command\Cache;

use App\Shared\Infrastructure\Cache\CacheWarmupService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Console command for warming up caches.
 */
#[AsCommand(
    name: 'cache:warmup:quiz',
    description: 'Warm up quiz application caches'
)]
final class CacheWarmupCommand extends Command
{
    public function __construct(
        private readonly CacheWarmupService $warmupService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('type', InputArgument::OPTIONAL, 'Type of cache to warm (all, quiz, analytics, user, leaderboard)', 'all')
            ->addOption('smart', 's', InputOption::VALUE_NONE, 'Use smart warmup based on usage patterns')
            ->addOption('priority', 'p', InputOption::VALUE_OPTIONAL, 'Priority level (critical, important, all)', 'all')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be warmed without actually warming')
            ->addOption('concurrent', 'c', InputOption::VALUE_OPTIONAL, 'Number of concurrent warmup operations', 1)
            ->setHelp('This command warms up various caches to improve application performance.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $type = $input->getArgument('type');
        $smart = $input->getOption('smart');
        $priority = $input->getOption('priority');
        $dryRun = $input->getOption('dry-run');
        $concurrent = (int) $input->getOption('concurrent');

        $io->title('Quiz Application Cache Warmup');

        if ($dryRun) {
            $io->note('Running in dry-run mode - no actual cache warming will be performed');
        }

        if ($smart) {
            return $this->smartWarmup($io, $priority, $dryRun);
        }

        return $this->standardWarmup($io, $type, $dryRun, $concurrent);
    }

    private function standardWarmup(SymfonyStyle $io, string $type, bool $dryRun, int $concurrent): int
    {
        $startTime = microtime(true);
        
        if ($dryRun) {
            $this->showWarmupPlan($io, $type);
            return Command::SUCCESS;
        }

        $io->section(sprintf('Warming up %s cache(s)', $type));

        $progressBar = $io->createProgressBar();
        $progressBar->setFormat('verbose');
        $progressBar->start();

        $results = match ($type) {
            'all' => $this->warmupService->warmAllCaches(),
            'quiz' => ['quiz_data' => $this->warmupService->warmQuizData(['popular', 'recent'])],
            'analytics' => ['analytics_data' => $this->warmupService->warmAnalyticsData(['user_dashboard', 'admin_dashboard'])],
            'user' => ['user_data' => $this->warmupService->warmUserData(['1', '2', '3'])], // Mock user IDs
            'leaderboard' => ['leaderboard_data' => $this->warmupService->warmLeaderboardData(['global', 'weekly'])],
            default => throw new \InvalidArgumentException("Unknown cache type: {$type}")
        };

        $progressBar->finish();
        $io->newLine(2);

        $this->displayResults($io, $results, microtime(true) - $startTime);

        return Command::SUCCESS;
    }

    private function smartWarmup(SymfonyStyle $io, string $priority, bool $dryRun): int
    {
        $io->section('Smart Cache Warmup');
        $io->writeln('Analyzing usage patterns and determining optimal warmup strategy...');

        $priorities = $this->getPriorityConfig($priority);
        $context = $this->getWarmupContext();

        if ($dryRun) {
            $io->table(
                ['Priority', 'Cache Type', 'Reason'],
                [
                    ['Critical', 'Active Sessions', 'Users currently taking quizzes'],
                    ['Critical', 'Live Leaderboards', 'Real-time competitive data'],
                    ['Important', 'User Dashboards', 'Frequently accessed analytics'],
                    ['Important', 'Popular Questions', 'Most viewed quiz content'],
                    ['Nice to Have', 'Historical Data', 'Background analytics'],
                    ['Nice to Have', 'Trend Analysis', 'Long-term insights'],
                ]
            );
            return Command::SUCCESS;
        }

        $startTime = microtime(true);
        $results = $this->warmupService->smartWarmup($priorities, $context);

        $this->displaySmartResults($io, $results, microtime(true) - $startTime);

        return Command::SUCCESS;
    }

    private function showWarmupPlan(SymfonyStyle $io, string $type): void
    {
        $plans = [
            'all' => [
                'Quiz Data' => ['Popular questions', 'Recent sessions', 'Category statistics'],
                'Analytics Data' => ['User dashboards', 'Admin dashboards', 'Real-time metrics'],
                'User Data' => ['Active user profiles', 'User preferences', 'Progress data'],
                'Leaderboard Data' => ['Global rankings', 'Weekly rankings', 'Category rankings'],
            ],
            'quiz' => [
                'Quiz Data' => ['Popular questions', 'Recent sessions', 'Category statistics'],
            ],
            'analytics' => [
                'Analytics Data' => ['User dashboards', 'Admin dashboards', 'Real-time metrics'],
            ],
            'user' => [
                'User Data' => ['Active user profiles', 'User preferences', 'Progress data'],
            ],
            'leaderboard' => [
                'Leaderboard Data' => ['Global rankings', 'Weekly rankings', 'Category rankings'],
            ],
        ];

        $plan = $plans[$type] ?? [];

        $io->section('Warmup Plan');
        foreach ($plan as $category => $items) {
            $io->writeln("<info>{$category}:</info>");
            foreach ($items as $item) {
                $io->writeln("  • {$item}");
            }
            $io->newLine();
        }
    }

    private function displayResults(SymfonyStyle $io, array $results, float $duration): void
    {
        if (isset($results['summary'])) {
            // Results from warmAllCaches
            $summary = $results['summary'];
            
            $io->section('Warmup Summary');
            $summaryTable = [
                ['Total Operations', $summary['total_operations']],
                ['Successful Operations', $summary['successful_operations']],
                ['Success Rate', sprintf('%.1f%%', $summary['success_rate'] * 100)],
                ['Duration', sprintf('%.2f seconds', $duration)],
            ];
            $io->table(['Metric', 'Value'], $summaryTable);

            if (!empty($results['details'])) {
                $io->section('Detailed Results');
                foreach ($results['details'] as $category => $categoryResults) {
                    if (is_array($categoryResults)) {
                        $successful = count(array_filter($categoryResults));
                        $total = count($categoryResults);
                        $io->writeln(sprintf(
                            '<info>%s:</info> %d/%d successful',
                            ucfirst(str_replace('_', ' ', $category)),
                            $successful,
                            $total
                        ));
                    }
                }
            }
        } else {
            // Results from individual cache warmup
            $io->section('Warmup Results');
            foreach ($results as $category => $categoryResults) {
                if (is_array($categoryResults)) {
                    $successful = count(array_filter($categoryResults));
                    $total = count($categoryResults);
                    $status = $successful === $total ? '✅' : '⚠️';
                    
                    $io->writeln(sprintf(
                        '%s <info>%s:</info> %d/%d successful',
                        $status,
                        ucfirst(str_replace('_', ' ', $category)),
                        $successful,
                        $total
                    ));
                }
            }
        }

        if ($duration > 0) {
            $io->success(sprintf('Cache warmup completed in %.2f seconds', $duration));
        }
    }

    private function displaySmartResults(SymfonyStyle $io, array $results, float $duration): void
    {
        $io->section('Smart Warmup Results');

        foreach ($results as $phase => $phaseData) {
            $operations = $phaseData['operations'];
            $successful = count(array_filter($operations));
            $total = count($operations);
            $phaseDuration = $phaseData['duration'];
            $successRate = $phaseData['success_rate'];

            $icon = match ($phase) {
                'critical' => '🚨',
                'important' => '⚠️',
                'nice_to_have' => 'ℹ️',
                default => '•'
            };

            $io->writeln(sprintf(
                '%s <info>%s Phase:</info> %d/%d operations (%.1f%%) in %.2fs',
                $icon,
                ucfirst(str_replace('_', ' ', $phase)),
                $successful,
                $total,
                $successRate * 100,
                $phaseDuration
            ));

            // Show failed operations
            $failed = array_filter($operations, fn($success) => !$success);
            if (!empty($failed)) {
                foreach (array_keys($failed) as $failedOp) {
                    $io->writeln("  ❌ {$failedOp}");
                }
            }
        }

        $io->success(sprintf('Smart warmup completed in %.2f seconds', $duration));
    }

    private function getPriorityConfig(string $priority): array
    {
        return match ($priority) {
            'critical' => ['critical'],
            'important' => ['critical', 'important'],
            'all' => ['critical', 'important', 'nice_to_have'],
            default => ['critical', 'important']
        };
    }

    private function getWarmupContext(): array
    {
        return [
            'time_of_day' => date('H'),
            'day_of_week' => date('N'),
            'current_load' => 'normal', // Would be determined dynamically
            'active_competitions' => false, // Would be checked from database
        ];
    }
}