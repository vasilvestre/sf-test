<?php

declare(strict_types=1);

namespace App\Command\Cache;

use App\Shared\Infrastructure\Cache\CacheMonitoringService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Console command for monitoring cache health and performance.
 */
#[AsCommand(
    name: 'cache:monitor',
    description: 'Monitor cache health and performance'
)]
final class CacheMonitorCommand extends Command
{
    public function __construct(
        private readonly CacheMonitoringService $monitoringService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format (table, json)', 'table')
            ->addOption('watch', 'w', InputOption::VALUE_NONE, 'Watch mode - continuously monitor')
            ->addOption('interval', 'i', InputOption::VALUE_OPTIONAL, 'Interval for watch mode in seconds', 5)
            ->setHelp('This command monitors cache health and displays performance metrics.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $format = $input->getOption('format');
        $watch = $input->getOption('watch');
        $interval = (int) $input->getOption('interval');

        if ($watch) {
            return $this->watchMode($io, $format, $interval);
        }

        return $this->singleCheck($io, $format);
    }

    private function singleCheck(SymfonyStyle $io, string $format): int
    {
        $io->title('Cache Health Check');

        $healthStatus = $this->monitoringService->performHealthCheck();

        if ($format === 'json') {
            $output->writeln(json_encode($healthStatus, JSON_PRETTY_PRINT));
            return Command::SUCCESS;
        }

        // Display overall status
        $statusColor = match ($healthStatus['overall_status']) {
            'healthy' => 'green',
            'warning' => 'yellow',
            'critical' => 'red',
            default => 'white'
        };

        $io->writeln(sprintf(
            'Overall Status: <fg=%s>%s</>',
            $statusColor,
            strtoupper($healthStatus['overall_status'])
        ));

        // Display individual checks
        if (!empty($healthStatus['checks'])) {
            $io->section('Health Checks');
            $rows = [];
            
            foreach ($healthStatus['checks'] as $checkName => $checkData) {
                $status = $checkData['status'] ?? 'unknown';
                $statusIcon = match ($status) {
                    'healthy' => '✅',
                    'warning', 'degraded' => '⚠️',
                    'critical' => '❌',
                    default => '❓'
                };
                
                $rows[] = [
                    $checkName,
                    $statusIcon . ' ' . ucfirst($status),
                    $this->formatCheckDetails($checkData)
                ];
            }
            
            $io->table(['Check', 'Status', 'Details'], $rows);
        }

        // Display recommendations
        if (!empty($healthStatus['recommendations'])) {
            $io->section('Recommendations');
            foreach ($healthStatus['recommendations'] as $recommendation) {
                $icon = match ($recommendation['priority']) {
                    'critical' => '🚨',
                    'warning' => '⚠️',
                    default => 'ℹ️'
                };
                $io->writeln($icon . ' ' . $recommendation['message']);
            }
        }

        // Display performance metrics
        if (!empty($healthStatus['metrics'])) {
            $io->section('Performance Metrics');
            $summary = $healthStatus['metrics']['summary'];
            
            $metricsTable = [
                ['Overall Hit Ratio', sprintf('%.2f%%', $summary['overall_hit_ratio'] * 100)],
                ['Average Duration', sprintf('%.3f ms', $summary['average_duration'] * 1000)],
                ['Error Rate', sprintf('%.2f%%', $summary['error_rate'] * 100)],
                ['Performance Score', sprintf('%.1f/100', $summary['performance_score'])],
                ['Total Operations', number_format($summary['total_operations'])],
            ];
            
            $io->table(['Metric', 'Value'], $metricsTable);
        }

        $duration = $healthStatus['check_duration'] ?? 0;
        $io->success(sprintf('Health check completed in %.3f seconds', $duration));

        return $healthStatus['overall_status'] === 'healthy' ? Command::SUCCESS : Command::FAILURE;
    }

    private function watchMode(SymfonyStyle $io, string $format, int $interval): int
    {
        $io->title('Cache Monitor - Watch Mode');
        $io->writeln("Monitoring cache every {$interval} seconds. Press Ctrl+C to stop.\n");

        while (true) {
            $io->write("\033[2J\033[H"); // Clear screen and move cursor to top
            
            $healthStatus = $this->monitoringService->performHealthCheck();
            
            if ($format === 'json') {
                $io->writeln(json_encode($healthStatus, JSON_PRETTY_PRINT));
            } else {
                $this->displayWatchSummary($io, $healthStatus);
            }
            
            $io->writeln(sprintf("\nLast updated: %s", date('Y-m-d H:i:s')));
            
            sleep($interval);
        }

        return Command::SUCCESS;
    }

    private function displayWatchSummary(SymfonyStyle $io, array $healthStatus): void
    {
        // Display compact status for watch mode
        $statusColor = match ($healthStatus['overall_status']) {
            'healthy' => 'green',
            'warning' => 'yellow',
            'critical' => 'red',
            default => 'white'
        };

        $io->writeln(sprintf(
            'Status: <fg=%s>%s</>',
            $statusColor,
            strtoupper($healthStatus['overall_status'])
        ));

        if (!empty($healthStatus['metrics']['summary'])) {
            $summary = $healthStatus['metrics']['summary'];
            
            $io->writeln(sprintf(
                'Hit Ratio: %.1f%% | Latency: %.1fms | Errors: %.2f%% | Score: %.0f/100',
                $summary['overall_hit_ratio'] * 100,
                $summary['average_duration'] * 1000,
                $summary['error_rate'] * 100,
                $summary['performance_score']
            ));
        }

        // Show quick status of each check
        if (!empty($healthStatus['checks'])) {
            $statuses = [];
            foreach ($healthStatus['checks'] as $checkName => $checkData) {
                $status = $checkData['status'] ?? 'unknown';
                $icon = match ($status) {
                    'healthy' => '✅',
                    'warning', 'degraded' => '⚠️',
                    'critical' => '❌',
                    default => '❓'
                };
                $statuses[] = $icon . ' ' . ucfirst(str_replace('_', ' ', $checkName));
            }
            $io->writeln(implode(' | ', $statuses));
        }
    }

    private function formatCheckDetails(array $checkData): string
    {
        $details = [];
        
        if (isset($checkData['healthy_connections'], $checkData['total_connections'])) {
            $details[] = sprintf('%d/%d healthy', $checkData['healthy_connections'], $checkData['total_connections']);
        }
        
        if (isset($checkData['overall_hit_ratio'])) {
            $details[] = sprintf('%.1f%% hit ratio', $checkData['overall_hit_ratio'] * 100);
        }
        
        if (isset($checkData['average_duration'])) {
            $details[] = sprintf('%.1fms avg', $checkData['average_duration'] * 1000);
        }
        
        if (isset($checkData['usage_percentage'])) {
            $details[] = sprintf('%.1f%% memory', $checkData['usage_percentage'] * 100);
        }
        
        return implode(', ', $details) ?: 'No details available';
    }
}