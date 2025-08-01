<?php

declare(strict_types=1);

namespace App\Quiz\Infrastructure\Persistence\Doctrine\ORM\Entity;

use App\User\Infrastructure\Persistence\Doctrine\ORM\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'quizzes')]
class Quiz
{
    #[ORM\Id]
    #[ORM\Column(type: Types::GUID)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private string $id;

    #[ORM\Column(type: Types::STRING, length: 300)]
    private string $title;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: QuizTemplate::class)]
    #[ORM\JoinColumn(name: 'template_id', referencedColumnName: 'id')]
    private ?QuizTemplate $template = null;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'quizzes')]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id')]
    private ?Category $category = null;

    #[ORM\Column(type: Types::JSON, name: 'difficulty_range', nullable: true)]
    private ?array $difficultyRange = null; // {min: 1, max: 10}

    #[ORM\Column(type: Types::INTEGER, name: 'time_limit', nullable: true)]
    private ?int $timeLimit = null; // seconds

    #[ORM\Column(type: Types::INTEGER, name: 'max_attempts', nullable: true)]
    private ?int $maxAttempts = null;

    #[ORM\Column(type: Types::INTEGER, name: 'passing_score', options: ['default' => 70])]
    private int $passingScore = 70;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $configuration = null;

    #[ORM\Column(type: Types::BOOLEAN, name: 'is_published', options: ['default' => false])]
    private bool $isPublished = false;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'created_by', referencedColumnName: 'id')]
    private ?User $createdBy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'updated_at')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\OneToMany(targetEntity: QuizQuestion::class, mappedBy: 'quiz', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $quizQuestions;

    #[ORM\OneToMany(targetEntity: QuizAttempt::class, mappedBy: 'quiz')]
    private Collection $attempts;

    public function __construct(
        string $title,
        ?User $createdBy = null,
        ?string $description = null
    ) {
        $this->title = $title;
        $this->createdBy = $createdBy;
        $this->description = $description;
        $this->quizQuestions = new ArrayCollection();
        $this->attempts = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getTemplate(): ?QuizTemplate
    {
        return $this->template;
    }

    public function setTemplate(?QuizTemplate $template): self
    {
        $this->template = $template;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getDifficultyRange(): ?array
    {
        return $this->difficultyRange;
    }

    public function setDifficultyRange(?array $difficultyRange): self
    {
        $this->difficultyRange = $difficultyRange;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getTimeLimit(): ?int
    {
        return $this->timeLimit;
    }

    public function setTimeLimit(?int $timeLimit): self
    {
        $this->timeLimit = $timeLimit;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getMaxAttempts(): ?int
    {
        return $this->maxAttempts;
    }

    public function setMaxAttempts(?int $maxAttempts): self
    {
        $this->maxAttempts = $maxAttempts;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getPassingScore(): int
    {
        return $this->passingScore;
    }

    public function setPassingScore(int $passingScore): self
    {
        $this->passingScore = $passingScore;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getConfiguration(): ?array
    {
        return $this->configuration;
    }

    public function setConfiguration(?array $configuration): self
    {
        $this->configuration = $configuration;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function isPublished(): bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): self
    {
        $this->isPublished = $isPublished;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @return Collection<int, QuizQuestion>
     */
    public function getQuizQuestions(): Collection
    {
        return $this->quizQuestions;
    }

    public function addQuizQuestion(QuizQuestion $quizQuestion): self
    {
        if (!$this->quizQuestions->contains($quizQuestion)) {
            $this->quizQuestions->add($quizQuestion);
            $quizQuestion->setQuiz($this);
        }
        return $this;
    }

    public function removeQuizQuestion(QuizQuestion $quizQuestion): self
    {
        if ($this->quizQuestions->removeElement($quizQuestion)) {
            if ($quizQuestion->getQuiz() === $this) {
                $quizQuestion->setQuiz(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, QuizAttempt>
     */
    public function getAttempts(): Collection
    {
        return $this->attempts;
    }

    public function getQuestions(): Collection
    {
        return $this->quizQuestions->map(fn(QuizQuestion $qq) => $qq->getQuestion());
    }

    public function getTotalQuestions(): int
    {
        return $this->quizQuestions->count();
    }

    public function getEstimatedDuration(): int
    {
        return $this->quizQuestions->reduce(
            fn(int $total, QuizQuestion $qq) => $total + $qq->getQuestion()->getEstimatedTime(),
            0
        );
    }
}