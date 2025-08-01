<?php

declare(strict_types=1);

namespace App\Command\Migration;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:migrate:complete',
    description: 'Run complete migration from legacy to enhanced schema'
)]
class CompleteMigrationCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('skip-schema', null, InputOption::VALUE_NONE, 'Skip schema migration (if already done)')
            ->addOption('verify-only', null, InputOption::VALUE_NONE, 'Only run verification')
            ->addOption('no-verify', null, InputOption::VALUE_NONE, 'Skip verification step')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Complete Database Migration');

        $startTime = microtime(true);

        // Verification only mode
        if ($input->getOption('verify-only')) {
            return $this->runCommand('app:migrate:verify', [], $output);
        }

        // Step 1: Run schema migration (unless skipped)
        if (!$input->getOption('skip-schema')) {
            $io->section('Step 1: Creating new database schema');
            
            // Run doctrine migrations
            $result = $this->runCommand('doctrine:migrations:migrate', ['--no-interaction' => true], $output);
            if ($result !== Command::SUCCESS) {
                $io->error('Schema migration failed');
                return Command::FAILURE;
            }
            
            $io->success('Schema migration completed');
        } else {
            $io->note('Skipping schema migration');
        }

        // Step 2: Migrate categories
        $io->section('Step 2: Migrating categories');
        $result = $this->runCommand('app:migrate:categories', [], $output);
        if ($result !== Command::SUCCESS) {
            $io->error('Category migration failed');
            return Command::FAILURE;
        }

        // Step 3: Migrate questions
        $io->section('Step 3: Migrating questions and answers');
        $result = $this->runCommand('app:migrate:questions', [], $output);
        if ($result !== Command::SUCCESS) {
            $io->error('Question migration failed');
            return Command::FAILURE;
        }

        // Step 4: Migrate quiz results
        $io->section('Step 4: Migrating quiz results');
        $result = $this->runCommand('app:migrate:quiz-results', [], $output);
        if ($result !== Command::SUCCESS) {
            $io->error('Quiz results migration failed');
            return Command::FAILURE;
        }

        // Step 5: Generate analytics
        $io->section('Step 5: Generating analytics from historical data');
        $result = $this->runCommand('app:migrate:analytics', [], $output);
        if ($result !== Command::SUCCESS) {
            $io->error('Analytics generation failed');
            return Command::FAILURE;
        }

        // Step 6: Verification (unless skipped)
        if (!$input->getOption('no-verify')) {
            $io->section('Step 6: Verifying migration integrity');
            $result = $this->runCommand('app:migrate:verify', [], $output);
            if ($result !== Command::SUCCESS) {
                $io->error('Migration verification failed');
                return Command::FAILURE;
            }
        } else {
            $io->note('Skipping verification');
        }

        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);

        $io->success(sprintf('Complete migration finished successfully in %s seconds', $duration));
        
        // Show next steps
        $this->showNextSteps($io);

        return Command::SUCCESS;
    }

    private function runCommand(string $command, array $arguments, OutputInterface $output): int
    {
        $command = $this->getApplication()->find($command);
        $input = new ArrayInput($arguments);
        
        return $command->run($input, $output);
    }

    private function showNextSteps(SymfonyStyle $io): void
    {
        $io->section('Next Steps');
        
        $io->listing([
            'Update your application configuration to use the new entity mappings',
            'Test the new quiz functionality with migrated data',
            'Update any custom queries to use the new schema',
            'Consider setting up monitoring for the new analytics tables',
            'Update your backup procedures to include the new tables',
            'Review and optimize query performance with the new indexes',
            'Test user authentication and authorization with the new User entities',
            'Verify that all application features work with the enhanced schema'
        ]);

        $io->note([
            'The legacy tables are still intact and can be used for rollback if needed.',
            'Once you\'re satisfied with the migration, you can drop the legacy tables.',
            'Remember to update your documentation to reflect the new schema structure.'
        ]);

        $io->block([
            'Migration completed successfully!',
            'Your quiz application now has an enterprise-grade database schema',
            'with enhanced user management, rich quiz features, and comprehensive analytics.'
        ], 'INFO', 'fg=green;bg=default', ' ', true);
    }
}