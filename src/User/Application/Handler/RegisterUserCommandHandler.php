<?php

declare(strict_types=1);

namespace App\User\Application\Handler;

use App\Shared\Application\Command\CommandHandlerInterface;
use App\Shared\Application\Command\CommandInterface;
use App\Shared\Domain\ValueObject\Id;
use App\User\Application\Command\RegisterUserCommand;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserAlreadyExistsException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\Password;
use App\User\Domain\ValueObject\Role;
use App\User\Domain\ValueObject\Username;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Handler for the RegisterUserCommand.
 * 
 * Handles user registration following DDD principles with proper validation,
 * password hashing, and duplicate checking.
 */
#[AsMessageHandler]
final class RegisterUserCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly LoggerInterface $logger
    ) {
    }

    public function handle(CommandInterface $command): mixed
    {
        if (!$command instanceof RegisterUserCommand) {
            throw new \InvalidArgumentException('Expected RegisterUserCommand');
        }
        
        $this->__invoke($command);
        
        return null;
    }

    public function __invoke(RegisterUserCommand $command): void
    {
        $this->logger->info('Processing user registration', [
            'email' => $command->email,
            'username' => $command->username,
            'role' => $command->role
        ]);

        // Create value objects with validation
        $email = Email::fromString($command->email);
        $username = Username::fromString($command->username);
        $role = Role::fromString($command->role);

        // Check if user already exists
        if ($this->userRepository->existsByEmail($email)) {
            throw new UserAlreadyExistsException(
                sprintf('User with email "%s" already exists', $command->email)
            );
        }

        if ($this->userRepository->existsByUsername($username)) {
            throw new UserAlreadyExistsException(
                sprintf('User with username "%s" already exists', $command->username)
            );
        }

        // Generate unique ID
        $id = Id::generate();

        // Hash the password using Symfony's password hasher
        // Create a temporary user instance for password hashing
        $tempUser = new \App\User\Infrastructure\Persistence\Doctrine\ORM\Entity\User(
            $command->email,
            $command->username,
            'temp', // temporary password
            [$command->role]
        );
        $hashedPassword = $this->passwordHasher->hashPassword($tempUser, $command->plainPassword);
        $password = Password::fromHash($hashedPassword);

        // Create and register the user
        $user = User::register(
            $id,
            $email,
            $username,
            $password,
            $role
        );

        // Save the user
        $this->userRepository->save($user);

        $this->logger->info('User registered successfully', [
            'userId' => $id->getValue(),
            'email' => $command->email,
            'username' => $command->username
        ]);
    }
}