<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Entity;

use App\Quiz\Domain\ValueObject\Score;
use App\Shared\Domain\Entity\AggregateRoot;
use App\Shared\Domain\ValueObject\Id;

/**
 * Enhanced QuizAttempt aggregate root for tracking quiz attempts.
 * Records user performance with detailed analytics and scoring.
 */
final class EnhancedQuizAttempt extends AggregateRoot
{
    private ?Id $id = null;
    private Id $userId;
    private Id $quizId;
    private int $attemptNumber;
    private \DateTimeImmutable $startedAt;
    private ?\DateTimeImmutable $completedAt = null;
    private ?\DateTimeImmutable $submittedAt = null;
    private string $status = 'in_progress';
    private ?Score $finalScore = null;
    private array $performanceMetrics = [];

    /** @var UserAnswer[] */
    private array $userAnswers = [];

    /** @var array */
    private array $metadata = [];

    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_ABANDONED = 'abandoned';
    public const STATUS_EXPIRED = 'expired';

    public function __construct(
        Id $userId,
        Id $quizId,
        int $attemptNumber
    ) {
        if ($attemptNumber <= 0) {
            throw new \InvalidArgumentException('Attempt number must be positive');
        }

        $this->userId = $userId;
        $this->quizId = $quizId;
        $this->attemptNumber = $attemptNumber;
        $this->startedAt = new \DateTimeImmutable();

        $this->recordEvent(new QuizAttemptStarted($this));
    }

    public function getId(): ?Id
    {
        return $this->id;
    }

    public function setId(Id $id): void
    {
        if ($this->id !== null) {
            throw new \LogicException('QuizAttempt ID cannot be changed once set');
        }
        $this->id = $id;
    }

    public function getUserId(): Id
    {
        return $this->userId;
    }

    public function getQuizId(): Id
    {
        return $this->quizId;
    }

    public function getAttemptNumber(): int
    {
        return $this->attemptNumber;
    }

    public function getStartedAt(): \DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function getSubmittedAt(): ?\DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getFinalScore(): ?Score
    {
        return $this->finalScore;
    }

    public function getPerformanceMetrics(): array
    {
        return $this->performanceMetrics;
    }

    /**
     * @return UserAnswer[]
     */
    public function getUserAnswers(): array
    {
        return $this->userAnswers;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    // Status checks
    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isSubmitted(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function isAbandoned(): bool
    {
        return $this->status === self::STATUS_ABANDONED;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    public function isFinished(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_SUBMITTED,
            self::STATUS_ABANDONED,
            self::STATUS_EXPIRED,
        ], true);
    }

    // Answer management
    public function addUserAnswer(UserAnswer $userAnswer): void
    {
        if (!$this->isInProgress()) {
            throw new \DomainException('Cannot add answers to a finished attempt');
        }

        // Replace existing answer for the same question
        $this->removeAnswerForQuestion($userAnswer->getQuestionId());
        $this->userAnswers[] = $userAnswer;

        $this->recordEvent(new UserAnswerAdded($this, $userAnswer));
    }

    public function removeAnswerForQuestion(Id $questionId): void
    {
        foreach ($this->userAnswers as $index => $userAnswer) {
            if ($userAnswer->getQuestionId()->equals($questionId)) {
                unset($this->userAnswers[$index]);
                $this->userAnswers = array_values($this->userAnswers);
                return;
            }
        }
    }

    public function getAnswerForQuestion(Id $questionId): ?UserAnswer
    {
        foreach ($this->userAnswers as $userAnswer) {
            if ($userAnswer->getQuestionId()->equals($questionId)) {
                return $userAnswer;
            }
        }
        return null;
    }

    public function hasAnswerForQuestion(Id $questionId): bool
    {
        return $this->getAnswerForQuestion($questionId) !== null;
    }

    public function getAnsweredQuestionCount(): int
    {
        return count($this->userAnswers);
    }

    // Status transitions
    public function complete(Score $finalScore, array $performanceMetrics = []): void
    {
        if (!$this->isInProgress()) {
            throw new \DomainException('Can only complete in-progress attempts');
        }

        $this->status = self::STATUS_COMPLETED;
        $this->completedAt = new \DateTimeImmutable();
        $this->finalScore = $finalScore;
        $this->performanceMetrics = $performanceMetrics;

        $this->recordEvent(new QuizAttemptCompleted($this));
    }

    public function submit(): void
    {
        if (!$this->isCompleted()) {
            throw new \DomainException('Can only submit completed attempts');
        }

        $this->status = self::STATUS_SUBMITTED;
        $this->submittedAt = new \DateTimeImmutable();

        $this->recordEvent(new QuizAttemptSubmitted($this));
    }

    public function abandon(): void
    {
        if ($this->isFinished()) {
            throw new \DomainException('Cannot abandon a finished attempt');
        }

        $this->status = self::STATUS_ABANDONED;
        $this->completedAt = new \DateTimeImmutable();

        $this->recordEvent(new QuizAttemptAbandoned($this));
    }

    public function expire(): void
    {
        if ($this->isFinished()) {
            throw new \DomainException('Cannot expire a finished attempt');
        }

        $this->status = self::STATUS_EXPIRED;
        $this->completedAt = new \DateTimeImmutable();

        $this->recordEvent(new QuizAttemptExpired($this));
    }

    // Performance analytics
    public function getDuration(): ?\DateInterval
    {
        if ($this->completedAt === null) {
            return null;
        }

        return $this->startedAt->diff($this->completedAt);
    }

    public function getDurationInSeconds(): ?int
    {
        $duration = $this->getDuration();
        if ($duration === null) {
            return null;
        }

        return ($duration->days * 24 * 60 * 60) +
               ($duration->h * 60 * 60) +
               ($duration->i * 60) +
               $duration->s;
    }

    public function getAverageTimePerQuestion(): ?float
    {
        $duration = $this->getDurationInSeconds();
        $questionCount = $this->getAnsweredQuestionCount();

        if ($duration === null || $questionCount === 0) {
            return null;
        }

        return $duration / $questionCount;
    }

    public function addPerformanceMetric(string $key, mixed $value): void
    {
        $this->performanceMetrics[$key] = $value;
    }

    public function getPerformanceMetric(string $key, mixed $default = null): mixed
    {
        return $this->performanceMetrics[$key] ?? $default;
    }

    // Metadata management
    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function addMetadata(string $key, mixed $value): void
    {
        $this->metadata[$key] = $value;
    }

    public function getMetadataValue(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    // Validation
    public function isValid(): bool
    {
        return $this->attemptNumber > 0 && !empty($this->userAnswers);
    }

    // Factory methods
    public static function start(Id $userId, Id $quizId, int $attemptNumber): self
    {
        return new self($userId, $quizId, $attemptNumber);
    }
}