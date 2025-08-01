<?php

declare(strict_types=1);

namespace App\Tests\Quiz\Application\Handler;

use App\Quiz\Application\Command\SubmitQuizAttemptCommand;
use App\Quiz\Application\Handler\SubmitQuizAttemptCommandHandler;
use App\Quiz\Domain\Repository\EnhancedQuizRepositoryInterface;
use App\Quiz\Domain\Repository\EnhancedQuizAttemptRepositoryInterface;
use App\Quiz\Domain\Entity\EnhancedQuiz;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit test for SubmitQuizAttemptCommandHandler.
 */
final class SubmitQuizAttemptCommandHandlerTest extends TestCase
{
    private EnhancedQuizRepositoryInterface|MockObject $quizRepository;
    private EnhancedQuizAttemptRepositoryInterface|MockObject $attemptRepository;
    private SubmitQuizAttemptCommandHandler $handler;

    protected function setUp(): void
    {
        $this->quizRepository = $this->createMock(EnhancedQuizRepositoryInterface::class);
        $this->attemptRepository = $this->createMock(EnhancedQuizAttemptRepositoryInterface::class);
        $this->handler = new SubmitQuizAttemptCommandHandler(
            $this->quizRepository,
            $this->attemptRepository
        );
    }

    public function testHandleSubmitsQuizAttemptSuccessfully(): void
    {
        // Arrange
        $command = new SubmitQuizAttemptCommand(
            userId: 1,
            quizId: 1,
            answers: [1 => 'A', 2 => 'B', 3 => 'C'],
            timeSpent: 300,
            startedAt: new \DateTimeImmutable('2024-01-01 10:00:00'),
            completedAt: new \DateTimeImmutable('2024-01-01 10:05:00')
        );

        $quiz = $this->createMock(EnhancedQuiz::class);
        $quiz->method('getCategoryId')->willReturn(1);

        $this->quizRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($quiz);

        $this->attemptRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->anything());

        // Act
        $results = $this->handler->__invoke($command);

        // Assert
        $this->assertIsArray($results);
        $this->assertArrayHasKey('score', $results);
        $this->assertArrayHasKey('correct_answers', $results);
        $this->assertArrayHasKey('total_questions', $results);
        $this->assertArrayHasKey('time_spent', $results);
    }

    public function testHandleThrowsExceptionWhenQuizNotFound(): void
    {
        // Arrange
        $command = new SubmitQuizAttemptCommand(
            userId: 1,
            quizId: 999,
            answers: [1 => 'A'],
            timeSpent: 300,
            startedAt: new \DateTimeImmutable(),
            completedAt: new \DateTimeImmutable()
        );

        $this->quizRepository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->attemptRepository
            ->expects($this->never())
            ->method('save');

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Quiz with ID 999 not found');

        $this->handler->__invoke($command);
    }
}