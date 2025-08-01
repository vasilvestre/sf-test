<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Entity;

use App\Shared\Domain\Entity\AggregateRoot;
use App\Shared\Domain\ValueObject\Id;
use App\Shared\Domain\ValueObject\Text;

/**
 * Answer entity representing a possible answer to a question.
 */
final class Answer extends AggregateRoot
{
    private ?Id $id = null;
    private Text $text;
    private bool $isCorrect;
    private ?Question $question = null;

    public function __construct(Text $text, bool $isCorrect)
    {
        $this->text = $text;
        $this->isCorrect = $isCorrect;
    }

    public function getId(): ?Id
    {
        return $this->id;
    }

    public function setId(Id $id): void
    {
        if ($this->id !== null) {
            throw new \LogicException('Answer ID cannot be changed once set');
        }
        $this->id = $id;
    }

    public function getText(): Text
    {
        return $this->text;
    }

    public function isCorrect(): bool
    {
        return $this->isCorrect;
    }

    public function getQuestion(): ?Question
    {
        return $this->question;
    }

    public function assignToQuestion(Question $question): void
    {
        $this->question = $question;
    }

    public function updateText(Text $text): void
    {
        $this->text = $text;
    }

    public function markAsCorrect(): void
    {
        $this->isCorrect = true;
    }

    public function markAsIncorrect(): void
    {
        $this->isCorrect = false;
    }
}