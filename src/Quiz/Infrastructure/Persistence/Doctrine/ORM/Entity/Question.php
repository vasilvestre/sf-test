<?php

declare(strict_types=1);

namespace App\Quiz\Infrastructure\Persistence\Doctrine\ORM\Entity;

use App\User\Infrastructure\Persistence\Doctrine\ORM\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'questions')]
#[ORM\Index(columns: ['category_id'], name: 'idx_questions_category_id')]
#[ORM\Index(columns: ['difficulty_level'], name: 'idx_questions_difficulty')]
#[ORM\Index(columns: ['question_type_id'], name: 'idx_questions_type')]
#[ORM\Index(columns: ['is_active'], name: 'idx_questions_active')]
class Question
{
    #[ORM\Id]
    #[ORM\Column(type: Types::GUID)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'questions')]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id')]
    private Category $category;

    #[ORM\ManyToOne(targetEntity: QuestionType::class)]
    #[ORM\JoinColumn(name: 'question_type_id', referencedColumnName: 'id')]
    private QuestionType $questionType;

    #[ORM\Column(type: Types::JSON)]
    private array $content;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $explanation = null;

    #[ORM\Column(type: Types::INTEGER, name: 'difficulty_level', options: ['default' => 5])]
    private int $difficultyLevel = 5;

    #[ORM\Column(type: Types::INTEGER, name: 'estimated_time', options: ['default' => 30])]
    private int $estimatedTime = 30; // seconds

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, name: 'scoring_weight', options: ['default' => '1.0'])]
    private string $scoringWeight = '1.0';

    #[ORM\Column(type: Types::JSON, options: ['default' => '[]'])]
    private array $tags = [];

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $metadata = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'created_by', referencedColumnName: 'id')]
    private ?User $createdBy = null;

    #[ORM\Column(type: Types::BOOLEAN, name: 'is_active', options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'updated_at')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\OneToMany(targetEntity: Answer::class, mappedBy: 'question', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $answers;

    #[ORM\OneToMany(targetEntity: QuizQuestion::class, mappedBy: 'question')]
    private Collection $quizQuestions;

    public function __construct(
        Category $category,
        QuestionType $questionType,
        array $content,
        ?User $createdBy = null
    ) {
        $this->category = $category;
        $this->questionType = $questionType;
        $this->content = $content;
        $this->createdBy = $createdBy;
        $this->answers = new ArrayCollection();
        $this->quizQuestions = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function setCategory(Category $category): self
    {
        $this->category = $category;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getQuestionType(): QuestionType
    {
        return $this->questionType;
    }

    public function setQuestionType(QuestionType $questionType): self
    {
        $this->questionType = $questionType;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getContent(): array
    {
        return $this->content;
    }

    public function setContent(array $content): self
    {
        $this->content = $content;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getExplanation(): ?array
    {
        return $this->explanation;
    }

    public function setExplanation(?array $explanation): self
    {
        $this->explanation = $explanation;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getDifficultyLevel(): int
    {
        return $this->difficultyLevel;
    }

    public function setDifficultyLevel(int $difficultyLevel): self
    {
        $this->difficultyLevel = $difficultyLevel;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getEstimatedTime(): int
    {
        return $this->estimatedTime;
    }

    public function setEstimatedTime(int $estimatedTime): self
    {
        $this->estimatedTime = $estimatedTime;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getScoringWeight(): float
    {
        return (float) $this->scoringWeight;
    }

    public function setScoringWeight(float $scoringWeight): self
    {
        $this->scoringWeight = (string) $scoringWeight;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function setTags(array $tags): self
    {
        $this->tags = $tags;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): self
    {
        $this->metadata = $metadata;
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

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        $this->updatedAt = new \DateTimeImmutable();
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
     * @return Collection<int, Answer>
     */
    public function getAnswers(): Collection
    {
        return $this->answers;
    }

    public function addAnswer(Answer $answer): self
    {
        if (!$this->answers->contains($answer)) {
            $this->answers->add($answer);
            $answer->setQuestion($this);
        }
        return $this;
    }

    public function removeAnswer(Answer $answer): self
    {
        if ($this->answers->removeElement($answer)) {
            if ($answer->getQuestion() === $this) {
                $answer->setQuestion(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, QuizQuestion>
     */
    public function getQuizQuestions(): Collection
    {
        return $this->quizQuestions;
    }

    public function getCorrectAnswers(): Collection
    {
        return $this->answers->filter(fn(Answer $answer) => $answer->isCorrect());
    }
}