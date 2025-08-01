<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Entity;

use App\Quiz\Domain\Service\QuestionScoringStrategyFactory;
use App\Quiz\Domain\ValueObject\Score;
use App\Shared\Domain\ValueObject\Id;

/**
 * QuestionAnswer entity representing a user's response to a specific question.
 * Encapsulates scoring logic, answer validation, and performance tracking.
 */
final class QuestionAnswer
{
    private readonly Score $score;
    private readonly bool $isCorrect;
    private readonly \DateTimeImmutable $answeredAt;
    private readonly array $validationResults;

    /**
     * @param Id $id Unique identifier for this question answer
     * @param EnhancedQuestion $question The question being answered
     * @param array $submittedAnswers User's submitted answers (format varies by question type)
     * @param float $timeSpent Time spent on this question in seconds
     * @param array $metadata Additional metadata (hints used, partial matches, etc.)
     */
    public function __construct(
        private readonly Id $id,
        private readonly EnhancedQuestion $question,
        private readonly array $submittedAnswers,
        private readonly float $timeSpent,
        private readonly array $metadata = []
    ) {
        $this->validateTimeSpent($timeSpent);
        $this->validateSubmittedAnswers($submittedAnswers);
        
        $this->answeredAt = new \DateTimeImmutable();
        
        // Calculate score using appropriate strategy
        $this->score = $this->calculateScore();
        $this->isCorrect = $this->determineCorrectness();
        $this->validationResults = $this->validateAnswers();
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function getQuestion(): EnhancedQuestion
    {
        return $this->question;
    }

    /**
     * Get the user's submitted answers.
     * Format varies by question type:
     * - Multiple choice: array of selected answer IDs
     * - True/False: boolean value
     * - Code completion: string with code
     * - Essay: string with essay text
     * - Fill in the blank: array of strings for each blank
     */
    public function getSubmittedAnswers(): array
    {
        return $this->submittedAnswers;
    }

    public function getTimeSpent(): float
    {
        return $this->timeSpent;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getScore(): float
    {
        return $this->score->getPercentage() / 100.0; // Return as 0-1 range
    }

    public function getDetailedScore(): Score
    {
        return $this->score;
    }

    public function isCorrect(): bool
    {
        return $this->isCorrect;
    }

    public function getAnsweredAt(): \DateTimeImmutable
    {
        return $this->answeredAt;
    }

    public function getValidationResults(): array
    {
        return $this->validationResults;
    }

    /**
     * Check if hints were used during answering.
     */
    public function hintsUsed(): bool
    {
        return isset($this->metadata['hints_used']) && $this->metadata['hints_used'] > 0;
    }

    /**
     * Get the number of hints used.
     */
    public function getHintsUsedCount(): int
    {
        return $this->metadata['hints_used'] ?? 0;
    }

    /**
     * Check if this answer received partial credit.
     */
    public function hasPartialCredit(): bool
    {
        return $this->score->getPercentage() > 0 && $this->score->getPercentage() < 100;
    }

    /**
     * Get performance metrics for analytics.
     */
    public function getPerformanceMetrics(): array
    {
        $questionType = $this->question->getType();
        $difficulty = $this->question->getDifficultyLevel();
        
        $metrics = [
            'question_id' => $this->question->getId()?->toString(),
            'question_type' => $questionType->toString(),
            'difficulty_level' => $difficulty->getLevel(),
            'score_percentage' => $this->score->getPercentage(),
            'is_correct' => $this->isCorrect,
            'time_spent' => $this->timeSpent,
            'answered_at' => $this->answeredAt->format(\DateTimeInterface::ATOM),
            'hints_used' => $this->getHintsUsedCount(),
            'has_partial_credit' => $this->hasPartialCredit(),
        ];

        // Add question-specific metrics
        if ($questionType->isMultipleChoice()) {
            $metrics['selected_answers_count'] = count($this->submittedAnswers);
            $metrics['correct_answers_count'] = count($this->question->getCorrectAnswers());
        }

        if ($questionType->isCodeCompletion()) {
            $metrics['code_length'] = strlen($this->submittedAnswers[0] ?? '');
            $metrics['syntax_valid'] = $this->metadata['syntax_valid'] ?? false;
        }

        if ($questionType->isEssay()) {
            $metrics['word_count'] = str_word_count($this->submittedAnswers[0] ?? '');
            $metrics['character_count'] = strlen($this->submittedAnswers[0] ?? '');
            $metrics['requires_manual_grading'] = true;
        }

        return $metrics;
    }

    /**
     * Check if this answer meets minimum quality standards.
     */
    public function meetsQualityStandards(): bool
    {
        $questionType = $this->question->getType();

        // Time-based quality check
        if ($this->timeSpent < 1.0) {
            return false; // Too fast, likely not properly considered
        }

        // Type-specific quality checks
        if ($questionType->isEssay()) {
            $wordCount = str_word_count($this->submittedAnswers[0] ?? '');
            return $wordCount >= 10; // Minimum word count for essays
        }

        if ($questionType->isCodeCompletion()) {
            $code = $this->submittedAnswers[0] ?? '';
            return strlen(trim($code)) >= 5; // Minimum non-whitespace code
        }

        if ($questionType->isFillInTheBlank()) {
            // Check that all blanks are filled
            foreach ($this->submittedAnswers as $answer) {
                if (empty(trim($answer))) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get feedback for the user based on their answer.
     */
    public function getFeedback(): string
    {
        if ($this->isCorrect) {
            $feedback = "Correct! ";
            if ($this->hintsUsed()) {
                $feedback .= "Note: You used {$this->getHintsUsedCount()} hint(s).";
            } else {
                $feedback .= "Great job solving this without hints!";
            }
        } else {
            $feedback = "Incorrect. ";
            if ($this->hasPartialCredit()) {
                $feedback .= sprintf("You earned %.1f%% partial credit. ", $this->score->getPercentage());
            }
            
            // Add specific feedback based on question type
            $questionType = $this->question->getType();
            if ($questionType->isMultipleChoice()) {
                $correctCount = count($this->question->getCorrectAnswers());
                $selectedCount = count($this->submittedAnswers);
                if ($selectedCount < $correctCount) {
                    $feedback .= "You need to select more answers. ";
                } elseif ($selectedCount > $correctCount) {
                    $feedback .= "You selected too many answers. ";
                }
            }
        }

        // Add explanation if available
        $explanation = $this->question->getExplanation();
        if ($explanation) {
            $feedback .= "\n\nExplanation: " . $explanation->getText();
        }

        return $feedback;
    }

    private function calculateScore(): Score
    {
        $strategy = QuestionScoringStrategyFactory::create($this->question->getType());
        $baseScore = $strategy->calculateScore($this->question, $this->submittedAnswers);
        
        // Apply penalties for hints used
        if ($this->hintsUsed()) {
            $hintsUsed = $this->getHintsUsedCount();
            $hintPenalty = min(0.2 * $hintsUsed, 0.5); // Max 50% penalty for hints
            $penaltyMultiplier = 1.0 - $hintPenalty;
            
            $penalizedPoints = $baseScore->getPoints() * $penaltyMultiplier;
            $metadata = array_merge($baseScore->getMetadata(), [
                'hint_penalty_applied' => $hintPenalty,
                'original_score' => $baseScore->getPoints(),
                'hints_used' => $hintsUsed,
            ]);
            
            return Score::create($penalizedPoints, $baseScore->getMaxPoints())
                ->withMetadata($metadata);
        }
        
        return $baseScore;
    }

    private function determineCorrectness(): bool
    {
        // A question is considered correct if the score is 100%
        // or if it's an essay type that requires manual grading
        if ($this->question->getType()->isEssay()) {
            return $this->metadata['manually_graded_correct'] ?? false;
        }
        
        return $this->score->isPerfect();
    }

    private function validateAnswers(): array
    {
        $results = [
            'is_valid' => true,
            'errors' => [],
            'warnings' => [],
        ];

        $questionType = $this->question->getType();

        // Type-specific validation
        if ($questionType->isMultipleChoice() || $questionType->isSingleChoice()) {
            $this->validateChoiceAnswers($results);
        } elseif ($questionType->isCodeCompletion()) {
            $this->validateCodeAnswer($results);
        } elseif ($questionType->isEssay()) {
            $this->validateEssayAnswer($results);
        } elseif ($questionType->isFillInTheBlank()) {
            $this->validateFillInTheBlankAnswers($results);
        }

        return $results;
    }

    private function validateChoiceAnswers(array &$results): void
    {
        $questionType = $this->question->getType();
        $availableAnswers = $this->question->getAnswers();
        $availableAnswerIds = array_map(fn($answer) => $answer->getId()->toString(), $availableAnswers);

        // Check if all submitted answers are valid
        foreach ($this->submittedAnswers as $answerId) {
            if (!in_array($answerId, $availableAnswerIds)) {
                $results['errors'][] = "Invalid answer ID: {$answerId}";
                $results['is_valid'] = false;
            }
        }

        // Single choice should have exactly one answer
        if ($questionType->isSingleChoice() && count($this->submittedAnswers) !== 1) {
            $results['errors'][] = "Single choice questions must have exactly one answer";
            $results['is_valid'] = false;
        }

        // Multiple choice should have at least one answer
        if ($questionType->isMultipleChoice() && empty($this->submittedAnswers)) {
            $results['warnings'][] = "No answers selected for multiple choice question";
        }
    }

    private function validateCodeAnswer(array &$results): void
    {
        $code = $this->submittedAnswers[0] ?? '';
        
        if (empty(trim($code))) {
            $results['errors'][] = "Code completion answer cannot be empty";
            $results['is_valid'] = false;
        }

        // Basic syntax validation (this could be enhanced with actual parsing)
        if (isset($this->metadata['syntax_check']) && !$this->metadata['syntax_check']) {
            $results['warnings'][] = "Code may contain syntax errors";
        }
    }

    private function validateEssayAnswer(array &$results): void
    {
        $essay = $this->submittedAnswers[0] ?? '';
        
        if (empty(trim($essay))) {
            $results['errors'][] = "Essay answer cannot be empty";
            $results['is_valid'] = false;
        }

        $wordCount = str_word_count($essay);
        if ($wordCount < 5) {
            $results['warnings'][] = "Essay answer is very short (less than 5 words)";
        }
    }

    private function validateFillInTheBlankAnswers(array &$results): void
    {
        $expectedBlanks = $this->metadata['expected_blanks'] ?? count($this->submittedAnswers);
        
        if (count($this->submittedAnswers) !== $expectedBlanks) {
            $results['errors'][] = "Expected {$expectedBlanks} answers, got " . count($this->submittedAnswers);
            $results['is_valid'] = false;
        }

        foreach ($this->submittedAnswers as $index => $answer) {
            if (empty(trim($answer))) {
                $results['warnings'][] = "Blank " . ($index + 1) . " is empty";
            }
        }
    }

    private function validateTimeSpent(float $timeSpent): void
    {
        if ($timeSpent < 0) {
            throw new \InvalidArgumentException('Time spent cannot be negative');
        }

        if ($timeSpent > 3600) { // 1 hour max per question
            throw new \InvalidArgumentException('Time spent exceeds maximum allowed (1 hour)');
        }
    }

    private function validateSubmittedAnswers(array $submittedAnswers): void
    {
        if (empty($submittedAnswers)) {
            throw new \InvalidArgumentException('Submitted answers cannot be empty');
        }

        // Validate based on question type
        $questionType = $this->question->getType();
        
        if ($questionType->isTrueFalse()) {
            if (count($submittedAnswers) !== 1 || !is_bool($submittedAnswers[0])) {
                throw new \InvalidArgumentException('True/False questions must have exactly one boolean answer');
            }
        }

        if ($questionType->isEssay() || $questionType->isCodeCompletion()) {
            if (count($submittedAnswers) !== 1 || !is_string($submittedAnswers[0])) {
                throw new \InvalidArgumentException('Essay and code completion questions must have exactly one string answer');
            }
        }
    }
}