<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Entity;

use App\Quiz\Domain\ValueObject\Content;
use App\Shared\Domain\Entity\AggregateRoot;
use App\Shared\Domain\ValueObject\Id;

/**
 * Enhanced Answer entity supporting rich content and flexible correctness.
 * Represents an answer option for quiz questions with advanced features.
 */
final class EnhancedAnswer extends AggregateRoot
{
    private ?Id $id = null;
    private Content $content;
    private bool $isCorrect;
    private float $partialCreditPercentage;
    private ?int $position = null;
    private ?Content $feedback = null;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt = null;
    private ?EnhancedQuestion $question = null;

    /** @var array */
    private array $metadata = [];

    public function __construct(
        Content $content,
        bool $isCorrect = false,
        float $partialCreditPercentage = 0.0
    ) {
        $this->content = $content;
        $this->isCorrect = $isCorrect;
        $this->partialCreditPercentage = $this->validatePartialCredit($partialCreditPercentage);
        $this->createdAt = new \DateTimeImmutable();
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

    public function getContent(): Content
    {
        return $this->content;
    }

    public function isCorrect(): bool
    {
        return $this->isCorrect;
    }

    public function getPartialCreditPercentage(): float
    {
        return $this->partialCreditPercentage;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function getFeedback(): ?Content
    {
        return $this->feedback;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getQuestion(): ?EnhancedQuestion
    {
        return $this->question;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    // Content management
    public function updateContent(Content $content): void
    {
        $this->content = $content;
        $this->markAsUpdated();
    }

    public function setFeedback(Content $feedback): void
    {
        $this->feedback = $feedback;
        $this->markAsUpdated();
    }

    public function removeFeedback(): void
    {
        $this->feedback = null;
        $this->markAsUpdated();
    }

    // Correctness and scoring
    public function markAsCorrect(): void
    {
        $this->isCorrect = true;
        $this->partialCreditPercentage = 100.0;
        $this->markAsUpdated();
    }

    public function markAsIncorrect(): void
    {
        $this->isCorrect = false;
        $this->partialCreditPercentage = 0.0;
        $this->markAsUpdated();
    }

    public function setPartialCredit(float $percentage): void
    {
        $this->partialCreditPercentage = $this->validatePartialCredit($percentage);
        $this->isCorrect = $percentage > 0;
        $this->markAsUpdated();
    }

    public function hasPartialCredit(): bool
    {
        return $this->partialCreditPercentage > 0 && $this->partialCreditPercentage < 100;
    }

    public function getCreditPercentage(): float
    {
        return $this->isCorrect ? 100.0 : $this->partialCreditPercentage;
    }

    // Position management
    public function setPosition(int $position): void
    {
        if ($position < 0) {
            throw new \InvalidArgumentException('Position must be non-negative');
        }
        $this->position = $position;
        $this->markAsUpdated();
    }

    public function hasPosition(): bool
    {
        return $this->position !== null;
    }

    // Question assignment
    public function assignToQuestion(EnhancedQuestion $question): void
    {
        $this->question = $question;
    }

    public function unassignFromQuestion(): void
    {
        $this->question = null;
    }

    public function isAssignedToQuestion(): bool
    {
        return $this->question !== null;
    }

    // Metadata management
    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
        $this->markAsUpdated();
    }

    public function addMetadata(string $key, mixed $value): void
    {
        $this->metadata[$key] = $value;
        $this->markAsUpdated();
    }

    public function getMetadataValue(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    // Validation
    public function isValid(): bool
    {
        return !empty(trim($this->content->getText()));
    }

    // Comparison
    public function equals(AggregateRoot $other): bool
    {
        if (!$other instanceof EnhancedAnswer) {
            return false;
        }

        if ($this->id !== null && $other->id !== null) {
            return $this->id->equals($other->id);
        }

        // Compare by content if no IDs
        return $this->content->equals($other->content);
    }

    public function isSimilarTo(EnhancedAnswer $other, float $threshold = 0.8): bool
    {
        $text1 = strtolower(trim($this->content->getText()));
        $text2 = strtolower(trim($other->content->getText()));

        if ($text1 === $text2) {
            return true;
        }

        // Simple similarity check using Levenshtein distance
        $maxLength = max(strlen($text1), strlen($text2));
        if ($maxLength === 0) {
            return true;
        }

        $distance = levenshtein($text1, $text2);
        $similarity = 1 - ($distance / $maxLength);

        return $similarity >= $threshold;
    }

    // Helper methods
    private function validatePartialCredit(float $percentage): float
    {
        if ($percentage < 0 || $percentage > 100) {
            throw new \InvalidArgumentException('Partial credit percentage must be between 0 and 100');
        }

        return $percentage;
    }

    private function markAsUpdated(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Factory methods
    public static function correct(Content $content): self
    {
        return new self($content, true, 100.0);
    }

    public static function incorrect(Content $content): self
    {
        return new self($content, false, 0.0);
    }

    public static function partialCredit(Content $content, float $percentage): self
    {
        return new self($content, $percentage > 0, $percentage);
    }

    public static function trueFalse(bool $value): self
    {
        $content = Content::plainText($value ? 'True' : 'False');
        return new self($content, $value, $value ? 100.0 : 0.0);
    }
}