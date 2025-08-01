<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence\Doctrine\ORM\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'study_plans')]
class StudyPlan
{
    #[ORM\Id]
    #[ORM\Column(type: Types::GUID)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'studyPlans')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: Types::STRING, length: 200)]
    private string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::JSON, name: 'target_categories')]
    private array $targetCategories = [];

    #[ORM\Column(type: Types::DATE_MUTABLE, name: 'target_completion_date', nullable: true)]
    private ?\DateTime $targetCompletionDate = null;

    #[ORM\Column(type: Types::BOOLEAN, name: 'is_active', options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $progress = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'updated_at')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        User $user,
        string $name,
        ?string $description = null,
        array $targetCategories = [],
        ?\DateTime $targetCompletionDate = null
    ) {
        $this->user = $user;
        $this->name = $name;
        $this->description = $description;
        $this->targetCategories = $targetCategories;
        $this->targetCompletionDate = $targetCompletionDate;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
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

    public function getTargetCategories(): array
    {
        return $this->targetCategories;
    }

    public function setTargetCategories(array $targetCategories): self
    {
        $this->targetCategories = $targetCategories;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getTargetCompletionDate(): ?\DateTime
    {
        return $this->targetCompletionDate;
    }

    public function setTargetCompletionDate(?\DateTime $targetCompletionDate): self
    {
        $this->targetCompletionDate = $targetCompletionDate;
        $this->updatedAt = new \DateTimeImmutable();
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

    public function getProgress(): int
    {
        return $this->progress;
    }

    public function setProgress(int $progress): self
    {
        $this->progress = $progress;
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
}