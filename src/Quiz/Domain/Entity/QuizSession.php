<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Entity;

use App\Quiz\Domain\Event\QuizSessionStarted;
use App\Quiz\Domain\Event\QuizSessionCompleted;
use App\Quiz\Domain\Event\QuestionAnswered;
use App\Quiz\Domain\Entity\QuestionAnswer;
use App\Quiz\Domain\ValueObject\EnhancedDifficultyLevel;
use App\Quiz\Domain\ValueObject\TimeLimit;
use App\Shared\Domain\Entity\AggregateRoot;
use App\Shared\Domain\ValueObject\Id;
use App\User\Domain\Entity\UserId;

/**
 * Quiz session aggregate root.
 * Manages the state and flow of an active quiz-taking session.
 */
final class QuizSession extends AggregateRoot
{
    private array $questionAnswers = [];
    private int $currentQuestionIndex = 0;
    private ?\DateTimeImmutable $startedAt = null;
    private ?\DateTimeImmutable $completedAt = null;
    private ?float $totalTimeSpent = null;
    private bool $isCompleted = false;
    private array $adaptiveLearningData = [];

    public function __construct(
        private readonly Id $id,
        private readonly UserId $userId,
        private readonly array $questions,
        private readonly EnhancedDifficultyLevel $targetDifficulty,
        private readonly ?TimeLimit $timeLimit = null,
        private readonly bool $adaptiveLearning = true,
        private readonly bool $practiceMode = false,
        private readonly array $metadata = []
    ) {
        $this->startedAt = new \DateTimeImmutable();
        $this->recordEvent(new QuizSessionStarted(
            $this->id,
            $this->userId,
            count($this->questions),
            $this->targetDifficulty,
            $this->adaptiveLearning,
            $this->practiceMode
        ));
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function getUserId(): UserId
    {
        return $this->userId;
    }

    public function getQuestions(): array
    {
        return $this->questions;
    }

    public function getCurrentQuestion(): ?EnhancedQuestion
    {
        if ($this->currentQuestionIndex >= count($this->questions)) {
            return null;
        }

        return $this->questions[$this->currentQuestionIndex];
    }

    public function getCurrentQuestionIndex(): int
    {
        return $this->currentQuestionIndex;
    }

    public function getTotalQuestions(): int
    {
        return count($this->questions);
    }

    public function isCompleted(): bool
    {
        return $this->isCompleted;
    }

    public function isPracticeMode(): bool
    {
        return $this->practiceMode;
    }

    public function getTargetDifficulty(): EnhancedDifficultyLevel
    {
        return $this->targetDifficulty;
    }

    public function getTimeLimit(): ?TimeLimit
    {
        return $this->timeLimit;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function getTotalTimeSpent(): ?float
    {
        return $this->totalTimeSpent;
    }

    public function getQuestionAnswers(): array
    {
        return $this->questionAnswers;
    }

    public function getProgress(): float
    {
        if (empty($this->questions)) {
            return 100.0;
        }

        return (count($this->questionAnswers) / count($this->questions)) * 100;
    }

    public function getCorrectAnswersCount(): int
    {
        return array_reduce($this->questionAnswers, function (int $count, QuestionAnswer $answer) {
            return $answer->isCorrect() ? $count + 1 : $count;
        }, 0);
    }

    public function getScore(): float
    {
        if (empty($this->questionAnswers)) {
            return 0.0;
        }

        $totalScore = array_reduce($this->questionAnswers, function (float $score, QuestionAnswer $answer) {
            return $score + $answer->getScore();
        }, 0.0);

        return ($totalScore / count($this->questionAnswers)) * 100;
    }

    /**
     * Submit an answer for the current question.
     */
    public function submitAnswer(
        string $questionId,
        array $answers,
        float $timeSpent,
        ?array $metadata = null
    ): void {
        if ($this->isCompleted) {
            throw new \DomainException('Cannot submit answer to completed quiz session');
        }

        $currentQuestion = $this->getCurrentQuestion();
        if (!$currentQuestion || $currentQuestion->getId()->toString() !== $questionId) {
            throw new \DomainException('Question ID does not match current question');
        }

        // Create question answer
        $questionAnswer = new QuestionAnswer(
            new Id(),
            $currentQuestion,
            $answers,
            $timeSpent,
            $metadata ?? []
        );

        $this->questionAnswers[] = $questionAnswer;

        // Record domain event
        $this->recordEvent(new QuestionAnswered(
            $this->id,
            $currentQuestion->getId(),
            $answers,
            $questionAnswer->isCorrect(),
            $questionAnswer->getScore(),
            $timeSpent
        ));

        // Update adaptive learning data if enabled
        if ($this->adaptiveLearning) {
            $this->updateAdaptiveLearningData($questionAnswer);
        }

        // Move to next question
        $this->currentQuestionIndex++;
    }

    /**
     * Complete the quiz session.
     */
    public function complete(float $totalTimeSpent): void
    {
        if ($this->isCompleted) {
            throw new \DomainException('Quiz session is already completed');
        }

        $this->isCompleted = true;
        $this->completedAt = new \DateTimeImmutable();
        $this->totalTimeSpent = $totalTimeSpent;

        // Record domain event
        $this->recordEvent(new QuizSessionCompleted(
            $this->id,
            $this->userId,
            $this->getScore(),
            $this->getCorrectAnswersCount(),
            count($this->questions),
            $totalTimeSpent,
            $this->adaptiveLearningData
        ));
    }

    /**
     * Check if the session has timed out.
     */
    public function hasTimedOut(): bool
    {
        if (!$this->timeLimit || !$this->startedAt) {
            return false;
        }

        $elapsed = time() - $this->startedAt->getTimestamp();
        return $elapsed > $this->timeLimit->getSeconds();
    }

    /**
     * Get recommended next question based on adaptive learning.
     */
    public function getRecommendedNextQuestion(): ?EnhancedQuestion
    {
        if (!$this->adaptiveLearning || $this->currentQuestionIndex >= count($this->questions)) {
            return $this->getCurrentQuestion();
        }

        // Simple adaptive logic - can be enhanced with ML algorithms
        $recentPerformance = $this->getRecentPerformance();
        
        if ($recentPerformance < 0.5) {
            // User struggling - find easier question
            return $this->findQuestionByDifficulty($this->targetDifficulty->decrease());
        } elseif ($recentPerformance > 0.8) {
            // User performing well - find harder question
            return $this->findQuestionByDifficulty($this->targetDifficulty->increase());
        }

        return $this->getCurrentQuestion();
    }

    private function updateAdaptiveLearningData(QuestionAnswer $answer): void
    {
        $this->adaptiveLearningData[] = [
            'questionId' => $answer->getQuestion()->getId()->toString(),
            'correct' => $answer->isCorrect(),
            'score' => $answer->getScore(),
            'timeSpent' => $answer->getTimeSpent(),
            'difficulty' => $answer->getQuestion()->getDifficultyLevel()->getLevel(),
            'timestamp' => time(),
        ];
    }

    private function getRecentPerformance(int $lastNQuestions = 3): float
    {
        $recentAnswers = array_slice($this->questionAnswers, -$lastNQuestions);
        
        if (empty($recentAnswers)) {
            return 0.5; // Neutral performance
        }

        $totalScore = array_reduce($recentAnswers, function (float $score, QuestionAnswer $answer) {
            return $score + ($answer->isCorrect() ? 1 : 0);
        }, 0.0);

        return $totalScore / count($recentAnswers);
    }

    private function findQuestionByDifficulty(EnhancedDifficultyLevel $difficulty): ?EnhancedQuestion
    {
        $remainingQuestions = array_slice($this->questions, $this->currentQuestionIndex);
        
        foreach ($remainingQuestions as $question) {
            if ($question->getDifficultyLevel()->equals($difficulty)) {
                return $question;
            }
        }

        return $this->getCurrentQuestion();
    }
}