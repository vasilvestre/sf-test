<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Entity;

use App\Quiz\Domain\ValueObject\CategoryName;
use App\Shared\Domain\Entity\AggregateRoot;
use App\Shared\Domain\ValueObject\Id;
use App\Shared\Domain\ValueObject\Text;

/**
 * Category aggregate root representing a quiz category.
 */
final class Category extends AggregateRoot
{
    private ?Id $id = null;
    private CategoryName $name;
    private ?Text $description = null;
    private \DateTimeImmutable $createdAt;

    /** @var Question[] */
    private array $questions = [];

    public function __construct(CategoryName $name, ?Text $description = null)
    {
        $this->name = $name;
        $this->description = $description;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?Id
    {
        return $this->id;
    }

    public function setId(Id $id): void
    {
        if ($this->id !== null) {
            throw new \LogicException('Category ID cannot be changed once set');
        }
        $this->id = $id;
    }

    public function getName(): CategoryName
    {
        return $this->name;
    }

    public function getDescription(): ?Text
    {
        return $this->description;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updateName(CategoryName $name): void
    {
        $this->name = $name;
    }

    public function updateDescription(?Text $description): void
    {
        $this->description = $description;
    }

    public function addQuestion(Question $question): void
    {
        if (!in_array($question, $this->questions, true)) {
            $this->questions[] = $question;
            $question->assignToCategory($this);
        }
    }

    public function removeQuestion(Question $question): void
    {
        $key = array_search($question, $this->questions, true);
        if ($key !== false) {
            unset($this->questions[$key]);
            $this->questions = array_values($this->questions);
        }
    }

    /**
     * @return Question[]
     */
    public function getQuestions(): array
    {
        return $this->questions;
    }

    public function getQuestionCount(): int
    {
        return count($this->questions);
    }

    public function hasQuestions(): bool
    {
        return count($this->questions) > 0;
    }
}