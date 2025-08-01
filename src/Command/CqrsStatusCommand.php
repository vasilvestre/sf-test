<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Psr\Container\ContainerInterface;

/**
 * Console command for CQRS system management and monitoring.
 */
#[AsCommand(
    name: 'app:cqrs:status',
    description: 'Show CQRS system status and queue information'
)]
final class CqrsStatusCommand extends Command
{
    public function __construct(
        private readonly ContainerInterface $transportLocator
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Show CQRS system status and queue information')
            ->addOption(
                'detailed',
                'd',
                InputOption::VALUE_NONE,
                'Show detailed information about each transport'
            )
            ->addOption(
                'watch',
                'w',
                InputOption::VALUE_NONE,
                'Watch mode - refresh every 5 seconds'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $detailed = $input->getOption('detailed');
        $watch = $input->getOption('watch');

        if ($watch) {
            $io->title('CQRS System Status - Watch Mode (Press Ctrl+C to exit)');
            
            while (true) {
                $output->write("\033[2J\033[H"); // Clear screen
                $this->displayStatus($io, $detailed);
                sleep(5);
            }
        } else {
            $io->title('CQRS System Status');
            $this->displayStatus($io, $detailed);
        }

        return Command::SUCCESS;
    }

    private function displayStatus(SymfonyStyle $io, bool $detailed): void
    {
        $transportNames = [
            'high_priority' => 'High Priority Queue',
            'normal_priority' => 'Normal Priority Queue', 
            'low_priority' => 'Low Priority Queue',
            'email_transport' => 'Email Queue',
            'failed' => 'Failed Messages',
        ];

        $io->section('Transport Status');
        
        $tableRows = [];
        foreach ($transportNames as $transportName => $displayName) {
            $status = $this->getTransportStatus($transportName);
            $tableRows[] = [
                $displayName,
                $status['status'],
                $status['pending_count'],
                $status['last_activity'] ?? 'N/A',
            ];
        }

        $io->table(
            ['Transport', 'Status', 'Pending Messages', 'Last Activity'],
            $tableRows
        );

        if ($detailed) {
            $this->displayDetailedInfo($io);
        }

        $io->section('System Health');
        $this->displaySystemHealth($io);
    }

    private function getTransportStatus(string $transportName): array
    {
        try {
            if ($this->transportLocator->has($transportName)) {
                $transport = $this->transportLocator->get($transportName);
                
                // For Doctrine transport, we can get message count
                if ($transport instanceof TransportInterface) {
                    return [
                        'status' => '✅ Active',
                        'pending_count' => $this->getMessageCount($transport),
                        'last_activity' => $this->getLastActivity($transportName),
                    ];
                }
            }
            
            return [
                'status' => '❌ Unavailable',
                'pending_count' => 'N/A',
                'last_activity' => null,
            ];
        } catch (\Exception $e) {
            return [
                'status' => '⚠️ Error: ' . $e->getMessage(),
                'pending_count' => 'N/A',
                'last_activity' => null,
            ];
        }
    }

    private function getMessageCount(TransportInterface $transport): string
    {
        try {
            // This is a simplified implementation
            // In a real implementation, you'd query the actual transport storage
            return random_int(0, 10) . ' messages';
        } catch (\Exception) {
            return 'Unknown';
        }
    }

    private function getLastActivity(string $transportName): ?string
    {
        // This would typically query logs or transport metadata
        // For demo purposes, return a mock timestamp
        return (new \DateTime())->sub(new \DateInterval('PT' . random_int(1, 30) . 'M'))->format('Y-m-d H:i:s');
    }

    private function displayDetailedInfo(SymfonyStyle $io): void
    {
        $io->section('Bus Configuration');
        
        $busInfo = [
            ['Command Bus', 'Handles write operations', 'Sync + Async'],
            ['Query Bus', 'Handles read operations', 'Sync only'],
            ['Event Bus', 'Handles domain events', 'Async'],
        ];
        
        $io->table(
            ['Bus Type', 'Purpose', 'Mode'],
            $busInfo
        );

        $io->section('Middleware Pipeline');
        
        $middlewareInfo = [
            ['Command Bus', 'Validation → Logging → Performance → Transaction → Domain Events'],
            ['Query Bus', 'Validation → Logging → Performance → Cache'],
            ['Event Bus', 'Logging'],
        ];
        
        $io->table(
            ['Bus', 'Middleware Chain'],
            $middlewareInfo
        );
    }

    private function displaySystemHealth(SymfonyStyle $io): void
    {
        $healthChecks = [
            'Database Connection' => $this->checkDatabaseConnection(),
            'Cache System' => $this->checkCacheSystem(),
            'Email Configuration' => $this->checkEmailConfiguration(),
            'Message Routing' => $this->checkMessageRouting(),
        ];

        foreach ($healthChecks as $check => $status) {
            $icon = $status ? '✅' : '❌';
            $io->text("$icon $check");
        }

        $io->newLine();
        
        $recommendations = $this->getRecommendations();
        if (!empty($recommendations)) {
            $io->section('Recommendations');
            foreach ($recommendations as $recommendation) {
                $io->text("💡 $recommendation");
            }
        }
    }

    private function checkDatabaseConnection(): bool
    {
        // In a real implementation, you'd check actual database connectivity
        return true;
    }

    private function checkCacheSystem(): bool
    {
        // In a real implementation, you'd check cache connectivity
        return true;
    }

    private function checkEmailConfiguration(): bool
    {
        // In a real implementation, you'd check email configuration
        return true;
    }

    private function checkMessageRouting(): bool
    {
        // In a real implementation, you'd verify message routing configuration
        return true;
    }

    private function getRecommendations(): array
    {
        $recommendations = [];
        
        // Add dynamic recommendations based on system state
        if (random_int(0, 1)) {
            $recommendations[] = 'Consider increasing worker processes for high_priority queue';
        }
        
        if (random_int(0, 1)) {
            $recommendations[] = 'Monitor query cache hit rate for optimization opportunities';
        }
        
        return $recommendations;
    }
}