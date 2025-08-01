<?php

declare(strict_types=1);

namespace App\Quiz\Infrastructure\Persistence\Doctrine\ORM\Entity;

use App\Quiz\Domain\Entity\QuizSession as DomainQuizSession;
use App\Quiz\Domain\ValueObject\EnhancedDifficultyLevel;
use App\Quiz\Domain\ValueObject\TimeLimit;
use App\Shared\Domain\ValueObject\Id;
use App\User\Domain\Entity\UserId;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Doctrine ORM entity for QuizSession.
 * Maps domain QuizSession to database table.
 */
#[ORM\Entity]
#[ORM\Table(name: 'quiz_sessions')]
#[ORM\Index(columns: ['user_id', 'is_completed'], name: 'idx_user_completion')]
#[ORM\Index(columns: ['started_at'], name: 'idx_started_at')]
#[ORM\Index(columns: ['completed_at'], name: 'idx_completed_at')]
class QuizSession
{
    #[ORM\Id]
    #[ORM\Column(type: Types::GUID)]
    private string $id;

    #[ORM\Column(type: Types::GUID)]
    private string $userId;

    #[ORM\Column(type: Types::JSON)]
    private array $questions = [];

    #[ORM\Column(type: Types::JSON)]
    private array $questionAnswers = [];

    #[ORM\Column(type: Types::INTEGER)]
    private int $currentQuestionIndex = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $targetDifficultyLevel;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $timeLimitSeconds = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $adaptiveLearning = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $practiceMode = false;

    #[ORM\Column(type: Types::JSON)]
    private array $metadata = [];

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $startedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $totalTimeSpent = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isCompleted = false;

    #[ORM\Column(type: Types::JSON)]
    private array $adaptiveLearningData = [];

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public static function fromDomain(DomainQuizSession $domainSession): self
    {
        $entity = new self();
        $entity->id = $domainSession->getId()->toString();
        $entity->userId = $domainSession->getUserId()->toString();
        
        // Serialize questions
        $entity->questions = array_map(function ($question) {
            return [
                'id' => $question->getId()->toString(),
                'type' => $question->getType()->getValue(),
                'content' => $question->getContent()->getValue(),
                'difficulty' => $question->getDifficultyLevel()->getLevel(),
                'answers' => array_map(function ($answer) {
                    return [
                        'id' => $answer->getId()->toString(),
                        'content' => $answer->getContent()->getValue(),
                        'isCorrect' => $answer->isCorrect(),
                        'weight' => $answer->getWeight(),
                    ];
                }, $question->getAnswers()),
            ];
        }, $domainSession->getQuestions());

        // Serialize question answers
        $entity->questionAnswers = array_map(function ($answer) {
            return [
                'id' => $answer->getId()->toString(),
                'questionId' => $answer->getQuestion()->getId()->toString(),
                'answers' => $answer->getAnswers(),
                'timeSpent' => $answer->getTimeSpent(),
                'score' => $answer->getScore(),
                'isCorrect' => $answer->isCorrect(),
                'metadata' => $answer->getMetadata(),
            ];
        }, $domainSession->getQuestionAnswers());

        $entity->currentQuestionIndex = $domainSession->getCurrentQuestionIndex();
        $entity->targetDifficultyLevel = $domainSession->getTargetDifficulty()->getLevel();
        $entity->timeLimitSeconds = $domainSession->getTimeLimit()?->getSeconds();
        $entity->adaptiveLearning = $domainSession->isAdaptiveLearning();
        $entity->practiceMode = $domainSession->isPracticeMode();
        $entity->startedAt = $domainSession->getStartedAt() ?? new \DateTimeImmutable();
        $entity->completedAt = $domainSession->getCompletedAt();
        $entity->totalTimeSpent = $domainSession->getTotalTimeSpent();
        $entity->isCompleted = $domainSession->isCompleted();

        return $entity;
    }

    public function toDomain(): DomainQuizSession
    {
        // Reconstruct questions from serialized data
        $questions = array_map(function ($questionData) {
            // This would need proper question reconstruction
            // For now, returning a simplified version
            return new \App\Quiz\Domain\Entity\EnhancedQuestion(
                new Id($questionData['id']),
                new \App\Quiz\Domain\ValueObject\QuestionType($questionData['type']),
                new \App\Quiz\Domain\ValueObject\Content($questionData['content']),
                new EnhancedDifficultyLevel($questionData['difficulty'])
            );
        }, $this->questions);

        $timeLimit = $this->timeLimitSeconds ? new TimeLimit($this->timeLimitSeconds) : null;

        $session = new DomainQuizSession(
            new Id($this->id),
            new UserId($this->userId),
            $questions,
            new EnhancedDifficultyLevel($this->targetDifficultyLevel),
            $timeLimit,
            $this->adaptiveLearning,
            $this->practiceMode,
            $this->metadata
        );

        // Set the state
        $this->setSessionState($session);

        return $session;
    }

    private function setSessionState(DomainQuizSession $session): void
    {
        // Use reflection to set private properties
        $reflection = new \ReflectionClass($session);

        // Set question answers
        if (!empty($this->questionAnswers)) {
            $questionAnswersProperty = $reflection->getProperty('questionAnswers');
            $questionAnswersProperty->setAccessible(true);
            $questionAnswersProperty->setValue($session, $this->questionAnswers);
        }

        // Set current question index
        $currentQuestionIndexProperty = $reflection->getProperty('currentQuestionIndex');
        $currentQuestionIndexProperty->setAccessible(true);
        $currentQuestionIndexProperty->setValue($session, $this->currentQuestionIndex);

        // Set timestamps
        if ($this->startedAt) {
            $startedAtProperty = $reflection->getProperty('startedAt');
            $startedAtProperty->setAccessible(true);
            $startedAtProperty->setValue($session, $this->startedAt);
        }

        if ($this->completedAt) {
            $completedAtProperty = $reflection->getProperty('completedAt');
            $completedAtProperty->setAccessible(true);
            $completedAtProperty->setValue($session, $this->completedAt);
        }

        if ($this->totalTimeSpent !== null) {
            $totalTimeSpentProperty = $reflection->getProperty('totalTimeSpent');
            $totalTimeSpentProperty->setAccessible(true);
            $totalTimeSpentProperty->setValue($session, $this->totalTimeSpent);
        }

        // Set completion status
        $isCompletedProperty = $reflection->getProperty('isCompleted');
        $isCompletedProperty->setAccessible(true);
        $isCompletedProperty->setValue($session, $this->isCompleted);

        // Set adaptive learning data
        if (!empty($this->adaptiveLearningData)) {
            $adaptiveLearningDataProperty = $reflection->getProperty('adaptiveLearningData');
            $adaptiveLearningDataProperty->setAccessible(true);
            $adaptiveLearningDataProperty->setValue($session, $this->adaptiveLearningData);
        }
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Getters and setters for ORM
    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function setUserId(string $userId): void
    {
        $this->userId = $userId;
    }

    public function getQuestions(): array
    {
        return $this->questions;
    }

    public function setQuestions(array $questions): void
    {
        $this->questions = $questions;
    }

    public function getQuestionAnswers(): array
    {
        return $this->questionAnswers;
    }

    public function setQuestionAnswers(array $questionAnswers): void
    {
        $this->questionAnswers = $questionAnswers;
    }

    public function getCurrentQuestionIndex(): int
    {
        return $this->currentQuestionIndex;
    }

    public function setCurrentQuestionIndex(int $currentQuestionIndex): void
    {
        $this->currentQuestionIndex = $currentQuestionIndex;
    }

    public function getTargetDifficultyLevel(): int
    {
        return $this->targetDifficultyLevel;
    }

    public function setTargetDifficultyLevel(int $targetDifficultyLevel): void
    {
        $this->targetDifficultyLevel = $targetDifficultyLevel;
    }

    public function getTimeLimitSeconds(): ?int
    {
        return $this->timeLimitSeconds;
    }

    public function setTimeLimitSeconds(?int $timeLimitSeconds): void
    {
        $this->timeLimitSeconds = $timeLimitSeconds;
    }

    public function isAdaptiveLearning(): bool
    {
        return $this->adaptiveLearning;
    }

    public function setAdaptiveLearning(bool $adaptiveLearning): void
    {
        $this->adaptiveLearning = $adaptiveLearning;
    }

    public function isPracticeMode(): bool
    {
        return $this->practiceMode;
    }

    public function setPracticeMode(bool $practiceMode): void
    {
        $this->practiceMode = $practiceMode;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function getStartedAt(): \DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(\DateTimeImmutable $startedAt): void
    {
        $this->startedAt = $startedAt;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): void
    {
        $this->completedAt = $completedAt;
    }

    public function getTotalTimeSpent(): ?float
    {
        return $this->totalTimeSpent;
    }

    public function setTotalTimeSpent(?float $totalTimeSpent): void
    {
        $this->totalTimeSpent = $totalTimeSpent;
    }

    public function isCompleted(): bool
    {
        return $this->isCompleted;
    }

    public function setIsCompleted(bool $isCompleted): void
    {
        $this->isCompleted = $isCompleted;
    }

    public function getAdaptiveLearningData(): array
    {
        return $this->adaptiveLearningData;
    }

    public function setAdaptiveLearningData(array $adaptiveLearningData): void
    {
        $this->adaptiveLearningData = $adaptiveLearningData;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}