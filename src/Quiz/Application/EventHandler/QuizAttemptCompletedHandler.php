<?php

declare(strict_types=1);

namespace App\Quiz\Application\EventHandler;

use App\Quiz\Domain\Event\QuizAttemptCompleted;
use App\User\Application\Command\RecordAchievementCommand;
use Symfony\Component\Messenger\MessageBusInterface;
use Psr\Log\LoggerInterface;

/**
 * Event handler for quiz attempt completion events.
 */
final class QuizAttemptCompletedHandler
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(QuizAttemptCompleted $event): void
    {
        $this->logger->info('Processing QuizAttemptCompleted event', [
            'user_id' => $event->getUserId(),
            'quiz_id' => $event->getQuizId(),
            'score' => $event->getScore(),
        ]);

        // Log performance metrics (without analytics commands for now)
        $this->logger->info('Performance metrics logged', [
            'score' => $event->getScore(),
            'duration' => $event->getDuration(),
            'correct_answers' => $event->getCorrectAnswers(),
            'total_questions' => $event->getTotalQuestions(),
            'accuracy' => $event->getCorrectAnswers() / $event->getTotalQuestions() * 100,
        ]);
        
        // Update leaderboard (stubbed for now)
        $this->logger->info('Leaderboard update triggered', [
            'category_id' => $this->getQuizCategoryId($event->getQuizId()),
            'user_id' => $event->getUserId(),
            'score' => $event->getScore(),
        ]);
        
        // Check for achievements
        $this->checkAchievements($event);
        
        // Send performance summary email (if configured)
        $this->sendPerformanceSummary($event);
    }

    private function checkAchievements(QuizAttemptCompleted $event): void
    {
        // Check for high score achievement
        if ($event->getScore() >= 90.0) {
            $achievementCommand = new RecordAchievementCommand(
                userId: $event->getUserId(),
                achievementType: 'high_scorer',
                metadata: [
                    'score' => $event->getScore(),
                    'quiz_id' => $event->getQuizId(),
                ]
            );

            $this->commandBus->dispatch($achievementCommand);
        }

        // Check for speed achievement
        if ($event->getDuration() < 300) { // Less than 5 minutes
            $achievementCommand = new RecordAchievementCommand(
                userId: $event->getUserId(),
                achievementType: 'speed_demon',
                metadata: [
                    'duration' => $event->getDuration(),
                    'quiz_id' => $event->getQuizId(),
                ]
            );

            $this->commandBus->dispatch($achievementCommand);
        }
    }

    private function sendPerformanceSummary(QuizAttemptCompleted $event): void
    {
        // Implementation would send performance summary if user has enabled notifications
        $this->logger->debug('Performance summary prepared', [
            'user_id' => $event->getUserId(),
            'score' => $event->getScore(),
        ]);
    }

    private function getQuizCategoryId(int $quizId): int
    {
        // Implementation would fetch the category ID for the quiz
        return 1; // Mock category ID
    }
}