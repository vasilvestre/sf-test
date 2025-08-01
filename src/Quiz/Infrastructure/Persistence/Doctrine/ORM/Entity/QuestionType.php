<?php

declare(strict_types=1);

namespace App\Quiz\Infrastructure\Persistence\Doctrine\ORM\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'question_types')]
class QuestionType
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $id;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 100, name: 'scoring_strategy')]
    private string $scoringStrategy;

    #[ORM\Column(type: Types::BOOLEAN, name: 'is_active', options: ['default' => true])]
    private bool $isActive = true;

    public function __construct(
        string $id,
        string $name,
        string $scoringStrategy,
        ?string $description = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->scoringStrategy = $scoringStrategy;
        $this->description = $description;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getScoringStrategy(): string
    {
        return $this->scoringStrategy;
    }

    public function setScoringStrategy(string $scoringStrategy): self
    {
        $this->scoringStrategy = $scoringStrategy;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }
}