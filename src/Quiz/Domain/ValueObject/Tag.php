<?php

declare(strict_types=1);

namespace App\Quiz\Domain\ValueObject;

use App\Shared\Domain\ValueObject\AbstractValueObject;

/**
 * Value object representing a tag for categorization and search.
 * Supports hierarchical tagging and metadata.
 */
final class Tag extends AbstractValueObject
{
    public function __construct(
        private readonly string $name,
        private readonly ?string $category = null,
        private readonly array $metadata = []
    ) {
        if (empty(trim($name))) {
            throw new \InvalidArgumentException('Tag name cannot be empty');
        }

        if (strlen($name) > 50) {
            throw new \InvalidArgumentException('Tag name cannot be longer than 50 characters');
        }

        if (!preg_match('/^[a-zA-Z0-9\-_\s]+$/', $name)) {
            throw new \InvalidArgumentException('Tag name can only contain letters, numbers, hyphens, underscores, and spaces');
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function toString(): string
    {
        return $this->category ? "{$this->category}:{$this->name}" : $this->name;
    }

    public function getSlug(): string
    {
        return strtolower(str_replace([' ', '_'], '-', $this->name));
    }

    public function isInCategory(string $category): bool
    {
        return $this->category === $category;
    }

    public function withCategory(string $category): self
    {
        return new self($this->name, $category, $this->metadata);
    }

    public function withMetadata(array $metadata): self
    {
        return new self($this->name, $this->category, array_merge($this->metadata, $metadata));
    }

    // Factory methods
    public static function create(string $name, ?string $category = null): self
    {
        return new self($name, $category);
    }

    public static function skill(string $name): self
    {
        return new self($name, 'skill');
    }

    public static function topic(string $name): self
    {
        return new self($name, 'topic');
    }

    public static function level(string $name): self
    {
        return new self($name, 'level');
    }

    public static function language(string $name): self
    {
        return new self($name, 'language');
    }
}