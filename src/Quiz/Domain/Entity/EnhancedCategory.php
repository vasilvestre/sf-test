<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Entity;

use App\Quiz\Domain\ValueObject\Content;
use App\Quiz\Domain\ValueObject\Tag;
use App\Shared\Domain\Entity\AggregateRoot;
use App\Shared\Domain\ValueObject\Id;

/**
 * Enhanced Category aggregate root supporting hierarchical structure.
 * Represents a topic category with rich content and metadata.
 */
final class EnhancedCategory extends AggregateRoot
{
    private ?Id $id = null;
    private string $name;
    private ?Content $description = null;
    private ?self $parent = null;
    private string $slug;
    private int $sortOrder = 0;
    private bool $isActive = true;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt = null;

    /** @var self[] */
    private array $children = [];

    /** @var Tag[] */
    private array $tags = [];

    /** @var array */
    private array $metadata = [];

    public function __construct(
        string $name,
        ?Content $description = null,
        ?self $parent = null
    ) {
        if (empty(trim($name))) {
            throw new \InvalidArgumentException('Category name cannot be empty');
        }

        if (strlen($name) > 100) {
            throw new \InvalidArgumentException('Category name cannot be longer than 100 characters');
        }

        $this->name = $name;
        $this->description = $description;
        $this->parent = $parent;
        $this->slug = $this->generateSlug($name);
        $this->createdAt = new \DateTimeImmutable();

        if ($parent !== null) {
            $parent->addChild($this);
        }

        $this->recordEvent(new CategoryCreated($this));
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

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?Content
    {
        return $this->description;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @return self[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * @return Tag[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    // Basic information management
    public function updateName(string $name): void
    {
        if (empty(trim($name))) {
            throw new \InvalidArgumentException('Category name cannot be empty');
        }

        if (strlen($name) > 100) {
            throw new \InvalidArgumentException('Category name cannot be longer than 100 characters');
        }

        $this->name = $name;
        $this->slug = $this->generateSlug($name);
        $this->markAsUpdated();
        $this->recordEvent(new CategoryNameChanged($this, $name));
    }

    public function setDescription(Content $description): void
    {
        $this->description = $description;
        $this->markAsUpdated();
    }

    public function removeDescription(): void
    {
        $this->description = null;
        $this->markAsUpdated();
    }

    public function setSortOrder(int $sortOrder): void
    {
        if ($sortOrder < 0) {
            throw new \InvalidArgumentException('Sort order must be non-negative');
        }

        $this->sortOrder = $sortOrder;
        $this->markAsUpdated();
    }

    // Status management
    public function activate(): void
    {
        $this->isActive = true;
        $this->markAsUpdated();
        $this->recordEvent(new CategoryActivated($this));
    }

    public function deactivate(): void
    {
        $this->isActive = false;
        $this->markAsUpdated();
        $this->recordEvent(new CategoryDeactivated($this));
    }

    // Hierarchy management
    public function hasParent(): bool
    {
        return $this->parent !== null;
    }

    public function isRoot(): bool
    {
        return $this->parent === null;
    }

    public function hasChildren(): bool
    {
        return !empty($this->children);
    }

    public function isLeaf(): bool
    {
        return empty($this->children);
    }

    public function getLevel(): int
    {
        $level = 0;
        $current = $this->parent;

        while ($current !== null) {
            $level++;
            $current = $current->parent;
        }

        return $level;
    }

    public function getPath(): array
    {
        $path = [$this];
        $current = $this->parent;

        while ($current !== null) {
            array_unshift($path, $current);
            $current = $current->parent;
        }

        return $path;
    }

    public function getFullPath(): string
    {
        $names = array_map(fn(self $category) => $category->name, $this->getPath());
        return implode(' > ', $names);
    }

    public function changeParent(?self $newParent): void
    {
        // Prevent circular references
        if ($newParent !== null && $this->isAncestorOf($newParent)) {
            throw new \DomainException('Cannot set a descendant as parent');
        }

        $oldParent = $this->parent;

        // Remove from old parent
        if ($oldParent !== null) {
            $oldParent->removeChild($this);
        }

        // Set new parent
        $this->parent = $newParent;

        // Add to new parent
        if ($newParent !== null) {
            $newParent->addChild($this);
        }

        $this->markAsUpdated();
        $this->recordEvent(new CategoryParentChanged($this, $oldParent, $newParent));
    }

    public function addChild(self $child): void
    {
        if ($this->hasChild($child)) {
            return;
        }

        if ($child->isAncestorOf($this)) {
            throw new \DomainException('Cannot add ancestor as child');
        }

        $this->children[] = $child;
        $this->markAsUpdated();
        $this->recordEvent(new CategoryChildAdded($this, $child));
    }

    public function removeChild(self $child): void
    {
        foreach ($this->children as $index => $existingChild) {
            if ($existingChild->equals($child)) {
                unset($this->children[$index]);
                $this->children = array_values($this->children);
                $this->markAsUpdated();
                $this->recordEvent(new CategoryChildRemoved($this, $child));
                return;
            }
        }
    }

    public function hasChild(self $child): bool
    {
        foreach ($this->children as $existingChild) {
            if ($existingChild->equals($child)) {
                return true;
            }
        }
        return false;
    }

    public function isAncestorOf(self $category): bool
    {
        $current = $category->parent;

        while ($current !== null) {
            if ($current->equals($this)) {
                return true;
            }
            $current = $current->parent;
        }

        return false;
    }

    public function isDescendantOf(self $category): bool
    {
        return $category->isAncestorOf($this);
    }

    public function getAllDescendants(): array
    {
        $descendants = [];

        foreach ($this->children as $child) {
            $descendants[] = $child;
            $descendants = array_merge($descendants, $child->getAllDescendants());
        }

        return $descendants;
    }

    // Tag management
    public function addTag(Tag $tag): void
    {
        if ($this->hasTag($tag)) {
            return;
        }

        $this->tags[] = $tag;
        $this->markAsUpdated();
    }

    public function removeTag(Tag $tag): void
    {
        foreach ($this->tags as $index => $existingTag) {
            if ($existingTag->equals($tag)) {
                unset($this->tags[$index]);
                $this->tags = array_values($this->tags);
                $this->markAsUpdated();
                return;
            }
        }
    }

    public function hasTag(Tag $tag): bool
    {
        foreach ($this->tags as $existingTag) {
            if ($existingTag->equals($tag)) {
                return true;
            }
        }
        return false;
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

    // Validation and comparison
    public function equals(self $other): bool
    {
        if ($this->id !== null && $other->id !== null) {
            return $this->id->equals($other->id);
        }

        return $this->slug === $other->slug;
    }

    public function isValid(): bool
    {
        return !empty(trim($this->name));
    }

    // Helper methods
    private function generateSlug(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9\-_]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');

        return $slug ?: 'category';
    }

    private function markAsUpdated(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Factory methods
    public static function root(string $name, ?Content $description = null): self
    {
        return new self($name, $description, null);
    }

    public static function child(string $name, self $parent, ?Content $description = null): self
    {
        return new self($name, $description, $parent);
    }
}