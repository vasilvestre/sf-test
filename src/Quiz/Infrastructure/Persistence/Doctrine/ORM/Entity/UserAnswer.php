<?php

declare(strict_types=1);

namespace App\Quiz\Infrastructure\Persistence\Doctrine\ORM\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'user_answers')]
class UserAnswer
{
    #[ORM\Id]
    #[ORM\Column(type: Types::GUID)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: QuizAttempt::class, inversedBy: 'userAnswers')]
    #[ORM\JoinColumn(name: 'quiz_attempt_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private QuizAttempt $quizAttempt;

    #[ORM\ManyToOne(targetEntity: Question::class)]
    #[ORM\JoinColumn(name: 'question_id', referencedColumnName: 'id')]
    private Question $question;

    #[ORM\Column(type: Types::JSON, name: 'answer_ids', nullable: true)]
    private ?array $answerIds = null; // Array of selected answer IDs

    #[ORM\Column(type: Types::JSON, name: 'user_input', nullable: true)]
    private ?array $userInput = null; // For text inputs, essays, etc.

    #[ORM\Column(type: Types::BOOLEAN, name: 'is_correct', nullable: true)]
    private ?bool $isCorrect = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, name: 'score_earned', nullable: true)]
    private ?string $scoreEarned = null;

    #[ORM\Column(type: Types::INTEGER, name: 'time_spent', nullable: true)]
    private ?int $timeSpent = null; // seconds

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'answered_at')]
    private \DateTimeImmutable $answeredAt;

    public function __construct(QuizAttempt $quizAttempt, Question $question)
    {
        $this->quizAttempt = $quizAttempt;
        $this->question = $question;
        $this->answeredAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getQuizAttempt(): QuizAttempt
    {
        return $this->quizAttempt;
    }

    public function setQuizAttempt(?QuizAttempt $quizAttempt): self
    {
        $this->quizAttempt = $quizAttempt;
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

    public function getAnswerIds(): ?array
    {
        return $this->answerIds;
    }

    public function setAnswerIds(?array $answerIds): self
    {
        $this->answerIds = $answerIds;
        return $this;
    }

    public function getUserInput(): ?array
    {
        return $this->userInput;
    }

    public function setUserInput(?array $userInput): self
    {
        $this->userInput = $userInput;
        return $this;
    }

    public function isCorrect(): ?bool
    {
        return $this->isCorrect;
    }

    public function setIsCorrect(?bool $isCorrect): self
    {
        $this->isCorrect = $isCorrect;
        return $this;
    }

    public function getScoreEarned(): ?float
    {
        return $this->scoreEarned ? (float) $this->scoreEarned : null;
    }

    public function setScoreEarned(?float $scoreEarned): self
    {
        $this->scoreEarned = $scoreEarned ? (string) $scoreEarned : null;
        return $this;
    }

    public function getTimeSpent(): ?int
    {
        return $this->timeSpent;
    }

    public function setTimeSpent(?int $timeSpent): self
    {
        $this->timeSpent = $timeSpent;
        return $this;
    }

    public function getAnsweredAt(): \DateTimeImmutable
    {
        return $this->answeredAt;
    }

    public function setAnsweredAt(\DateTimeImmutable $answeredAt): self
    {
        $this->answeredAt = $answeredAt;
        return $this;
    }
}