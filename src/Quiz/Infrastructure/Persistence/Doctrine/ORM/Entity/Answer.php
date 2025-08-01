<?php

declare(strict_types=1);

namespace App\Quiz\Infrastructure\Persistence\Doctrine\ORM\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'answers')]
class Answer
{
    #[ORM\Id]
    #[ORM\Column(type: Types::GUID)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Question::class, inversedBy: 'answers')]
    #[ORM\JoinColumn(name: 'question_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Question $question;

    #[ORM\Column(type: Types::JSON)]
    private array $content;

    #[ORM\Column(type: Types::BOOLEAN, name: 'is_correct')]
    private bool $isCorrect;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $explanation = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, name: 'score_value', options: ['default' => '1.0'])]
    private string $scoreValue = '1.0';

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $position = 0;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $metadata = null;

    public function __construct(
        Question $question,
        array $content,
        bool $isCorrect,
        int $position = 0
    ) {
        $this->question = $question;
        $this->content = $content;
        $this->isCorrect = $isCorrect;
        $this->position = $position;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getQuestion(): Question
    {
        return $this->question;
    }

    public function setQuestion(?Question $question): self
    {
        $this->question = $question;
        return $this;
    }

    public function getContent(): array
    {
        return $this->content;
    }

    public function setContent(array $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function isCorrect(): bool
    {
        return $this->isCorrect;
    }

    public function setIsCorrect(bool $isCorrect): self
    {
        $this->isCorrect = $isCorrect;
        return $this;
    }

    public function getExplanation(): ?array
    {
        return $this->explanation;
    }

    public function setExplanation(?array $explanation): self
    {
        $this->explanation = $explanation;
        return $this;
    }

    public function getScoreValue(): float
    {
        return (float) $this->scoreValue;
    }

    public function setScoreValue(float $scoreValue): self
    {
        $this->scoreValue = (string) $scoreValue;
        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;
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
}