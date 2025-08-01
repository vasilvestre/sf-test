<?php

declare(strict_types=1);

namespace App\Quiz\Infrastructure\Persistence\Doctrine\ORM\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'quiz_questions')]
class QuizQuestion
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Quiz::class, inversedBy: 'quizQuestions')]
    #[ORM\JoinColumn(name: 'quiz_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Quiz $quiz;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Question::class, inversedBy: 'quizQuestions')]
    #[ORM\JoinColumn(name: 'question_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Question $question;

    #[ORM\Column(type: Types::INTEGER)]
    private int $position;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, options: ['default' => '1.0'])]
    private string $weight = '1.0';

    public function __construct(Quiz $quiz, Question $question, int $position, float $weight = 1.0)
    {
        $this->quiz = $quiz;
        $this->question = $question;
        $this->position = $position;
        $this->weight = (string) $weight;
    }

    public function getQuiz(): Quiz
    {
        return $this->quiz;
    }

    public function setQuiz(?Quiz $quiz): self
    {
        $this->quiz = $quiz;
        return $this;
    }

    public function getQuestion(): Question
    {
        return $this->question;
    }

    public function setQuestion(Question $question): self
    {
        $this->question = $question;
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

    public function getWeight(): float
    {
        return (float) $this->weight;
    }

    public function setWeight(float $weight): self
    {
        $this->weight = (string) $weight;
        return $this;
    }
}