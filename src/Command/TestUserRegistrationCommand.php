<?php

declare(strict_types=1);

namespace App\Command;

use App\User\Application\Command\RegisterUserCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'user:register',
    description: 'Register a new user for testing purposes',
)]
class TestUserRegistrationCommand extends Command
{
    public function __construct(
        private readonly MessageBusInterface $commandBus
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'User email')
            ->addArgument('username', InputArgument::REQUIRED, 'Username')
            ->addArgument('password', InputArgument::REQUIRED, 'Password')
            ->addArgument('role', InputArgument::OPTIONAL, 'User role', 'ROLE_STUDENT')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $email = $input->getArgument('email');
        $username = $input->getArgument('username');
        $password = $input->getArgument('password');
        $role = $input->getArgument('role');

        try {
            $command = new RegisterUserCommand(
                email: $email,
                username: $username,
                plainPassword: $password,
                role: $role
            );

            $this->commandBus->dispatch($command);

            $io->success("User '{$username}' registered successfully with email '{$email}' and role '{$role}'!");

            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $io->error('Registration failed: ' . $e->getMessage());
            $io->note('Full error: ' . get_class($e) . ': ' . $e->getMessage());
            
            if ($e->getPrevious()) {
                $io->note('Previous exception: ' . get_class($e->getPrevious()) . ': ' . $e->getPrevious()->getMessage());
            }
            
            return Command::FAILURE;
        }
    }
}