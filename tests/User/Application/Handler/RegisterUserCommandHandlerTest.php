<?php

declare(strict_types=1);

namespace App\Tests\User\Application\Handler;

use App\User\Application\Command\RegisterUserCommand;
use App\User\Application\Handler\RegisterUserCommandHandler;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\Entity\User;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit test for RegisterUserCommandHandler.
 */
final class RegisterUserCommandHandlerTest extends TestCase
{
    private UserRepositoryInterface|MockObject $userRepository;
    private RegisterUserCommandHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->handler = new RegisterUserCommandHandler($this->userRepository);
    }

    public function testHandleRegistersUserSuccessfully(): void
    {
        // Arrange
        $command = new RegisterUserCommand(
            email: 'test@example.com',
            username: 'testuser',
            plainPassword: 'password123',
            role: 'ROLE_STUDENT'
        );

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with('test@example.com')
            ->willReturn(null);

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(User::class));

        // Act & Assert
        $userId = $this->handler->__invoke($command);
        
        $this->assertIsInt($userId);
        $this->assertGreaterThan(0, $userId);
    }

    public function testHandleThrowsExceptionWhenUserAlreadyExists(): void
    {
        // Arrange
        $command = new RegisterUserCommand(
            email: 'existing@example.com',
            username: 'existinguser',
            plainPassword: 'password123'
        );

        $existingUser = $this->createMock(User::class);
        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with('existing@example.com')
            ->willReturn($existingUser);

        $this->userRepository
            ->expects($this->never())
            ->method('save');

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User with email existing@example.com already exists');

        $this->handler->__invoke($command);
    }
}