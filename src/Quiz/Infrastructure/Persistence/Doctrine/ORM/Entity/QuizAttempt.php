<?php

declare(strict_types=1);

namespace App\Quiz\Infrastructure\Persistence\Doctrine\ORM\Entity;

use App\User\Infrastructure\Persistence\Doctrine\ORM\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'quiz_attempts')]
#[ORM\Index(columns: ['user_id', 'quiz_id'], name: 'idx_quiz_attempts_user_quiz')]
#[ORM\Index(columns: ['status'], name: 'idx_quiz_attempts_status')]
#[ORM\Index(columns: ['completed_at'], name: 'idx_quiz_attempts_completed')]
#[ORM\UniqueConstraint(name: 'unique_user_quiz_attempt', columns: ['user_id', 'quiz_id', 'attempt_number'])]
class QuizAttempt
{
    #[ORM\Id]
    #[ORM\Column(type: Types::GUID)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Quiz::class, inversedBy: 'attempts')]
    #[ORM\JoinColumn(name: 'quiz_id', referencedColumnName: 'id')]
    private Quiz $quiz;

    #[ORM\Column(type: Types::INTEGER, name: 'attempt_number')]
    private int $attemptNumber;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'started_at')]
    private \DateTimeImmutable $startedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'completed_at', nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(type: Types::INTEGER, name: 'time_spent', nullable: true)]
    private ?int $timeSpent = null; // seconds

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, name: 'final_score', nullable: true)]
    private ?string $finalScore = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, name: 'max_possible_score', nullable: true)]
    private ?string $maxPossibleScore = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, name: 'percentage_score', nullable: true)]
    private ?string $percentageScore = null;

    #[ORM\Column(type: Types::STRING, length: 20, options: ['default' => 'in_progress'])]
    private string $status = 'in_progress'; // in_progress, completed, abandoned

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $metadata = null;

    #[ORM\OneToMany(targetEntity: UserAnswer::class, mappedBy: 'quizAttempt', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $userAnswers;

    public function __construct(User $user, Quiz $quiz, int $attemptNumber)
    {
        $this->user = $user;
        $this->quiz = $quiz;
        $this->attemptNumber = $attemptNumber;
        $this->startedAt = new \DateTimeImmutable();
        $this->userAnswers = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getQuiz(): Quiz
    {
        return $this->quiz;
    }

    public function setQuiz(Quiz $quiz): self
    {
        $this->quiz = $quiz;
        return $this;
    }

    public function getAttemptNumber(): int
    {
        return $this->attemptNumber;
    }

    public function setAttemptNumber(int $attemptNumber): self
    {
        $this->attemptNumber = $attemptNumber;
        return $this;
    }

    public function getStartedAt(): \DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(\DateTimeImmutable $startedAt): self
    {
        $this->startedAt = $startedAt;
        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): self
    {
        $this->completedAt = $completedAt;
        return $this;
    }

    public function getTimeSpent(): ?int
    {
        return $this->timeSpent;
    }

    public function setTimeSpent(?int $timeSpent): self
    {
        $this->timeSpent = $timeSpent;
        return $this;
    }

    public function getFinalScore(): ?float
    {
        return $this->finalScore ? (float) $this->finalScore : null;
    }

    public function setFinalScore(?float $finalScore): self
    {
        $this->finalScore = $finalScore ? (string) $finalScore : null;
        return $this;
    }

    public function getMaxPossibleScore(): ?float
    {
        return $this->maxPossibleScore ? (float) $this->maxPossibleScore : null;
    }

    public function setMaxPossibleScore(?float $maxPossibleScore): self
    {
        $this->maxPossibleScore = $maxPossibleScore ? (string) $maxPossibleScore : null;
        return $this;
    }

    public function getPercentageScore(): ?float
    {
        return $this->percentageScore ? (float) $this->percentageScore : null;
    }

    public function setPercentageScore(?float $percentageScore): self
    {
        $this->percentageScore = $percentageScore ? (string) $percentageScore : null;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * @return Collection<int, UserAnswer>
     */
    public function getUserAnswers(): Collection
    {
        return $this->userAnswers;
    }

    public function addUserAnswer(UserAnswer $userAnswer): self
    {
        if (!$this->userAnswers->contains($userAnswer)) {
            $this->userAnswers->add($userAnswer);
            $userAnswer->setQuizAttempt($this);
        }
        return $this;
    }

    public function removeUserAnswer(UserAnswer $userAnswer): self
    {
        if ($this->userAnswers->removeElement($userAnswer)) {
            if ($userAnswer->getQuizAttempt() === $this) {
                $userAnswer->setQuizAttempt(null);
            }
        }
        return $this;
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function complete(): self
    {
        $this->status = 'completed';
        $this->completedAt = new \DateTimeImmutable();
        
        if ($this->startedAt) {
            $this->timeSpent = $this->completedAt->getTimestamp() - $this->startedAt->getTimestamp();
        }
        
        return $this;
    }

    public function abandon(): self
    {
        $this->status = 'abandoned';
        return $this;
    }
}