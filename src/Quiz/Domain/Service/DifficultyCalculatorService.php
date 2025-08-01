<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Service;

use App\Quiz\Domain\Entity\EnhancedQuestion;
use App\Quiz\Domain\Entity\EnhancedQuizAttempt;
use App\Quiz\Domain\ValueObject\EnhancedDifficultyLevel;

/**
 * Domain service for calculating and adjusting question difficulty.
 * Implements adaptive difficulty algorithms based on user performance.
 */
final class DifficultyCalculatorService
{
    public function __construct(
        private readonly PerformanceAnalyzerService $performanceAnalyzer
    ) {
    }

    /**
     * Calculate difficulty level for a new question based on content analysis.
     */
    public function calculateInitialDifficulty(EnhancedQuestion $question): EnhancedDifficultyLevel
    {
        $score = 5; // Start with medium difficulty

        // Analyze question content complexity
        $score += $this->analyzeContentComplexity($question);

        // Analyze answer complexity
        $score += $this->analyzeAnswerComplexity($question);

        // Analyze question type complexity
        $score += $this->analyzeQuestionTypeComplexity($question);

        // Ensure score is within valid range
        $score = max(1, min(10, $score));

        return EnhancedDifficultyLevel::fromLevel($score);
    }

    /**
     * Adjust question difficulty based on performance data.
     */
    public function adjustDifficultyBasedOnPerformance(
        EnhancedQuestion $question,
        array $recentAttempts
    ): EnhancedDifficultyLevel {
        if (empty($recentAttempts)) {
            return $question->getDifficultyLevel();
        }

        $successRate = $this->calculateSuccessRate($question, $recentAttempts);
        $averageTime = $this->calculateAverageTime($question, $recentAttempts);
        
        $currentLevel = $question->getDifficultyLevel()->getLevel();
        $adjustment = 0;

        // Adjust based on success rate
        if ($successRate > 0.8) {
            $adjustment += 1; // Too easy, increase difficulty
        } elseif ($successRate < 0.4) {
            $adjustment -= 1; // Too hard, decrease difficulty
        }

        // Adjust based on average time
        $expectedTime = $this->getExpectedTimeForDifficulty($currentLevel);
        if ($averageTime !== null) {
            if ($averageTime < $expectedTime * 0.7) {
                $adjustment += 1; // Answered too quickly, might be too easy
            } elseif ($averageTime > $expectedTime * 1.5) {
                $adjustment -= 1; // Takes too long, might be too hard
            }
        }

        $newLevel = max(1, min(10, $currentLevel + $adjustment));
        return EnhancedDifficultyLevel::fromLevel($newLevel);
    }

    /**
     * Calculate personalized difficulty for a user based on their performance.
     */
    public function calculatePersonalizedDifficulty(
        string $userId,
        string $categoryId = null
    ): EnhancedDifficultyLevel {
        $userPerformance = $this->performanceAnalyzer->getUserPerformance($userId, $categoryId);
        
        if ($userPerformance === null) {
            return EnhancedDifficultyLevel::medium(); // Default for new users
        }

        $skillLevel = $userPerformance->getOverallSkillLevel();
        $confidence = $userPerformance->getConfidenceLevel();
        $improvementRate = $userPerformance->getImprovementRate();

        // Base difficulty on skill level
        $difficultyLevel = intval(round($skillLevel));

        // Adjust based on confidence
        if ($confidence > 0.8) {
            $difficultyLevel += 1; // Confident users can handle harder questions
        } elseif ($confidence < 0.4) {
            $difficultyLevel -= 1; // Less confident users need easier questions
        }

        // Adjust based on improvement rate
        if ($improvementRate > 0.1) {
            $difficultyLevel += 1; // Rapidly improving users can be challenged more
        } elseif ($improvementRate < -0.1) {
            $difficultyLevel -= 1; // Declining users need easier questions
        }

        $difficultyLevel = max(1, min(10, $difficultyLevel));
        return EnhancedDifficultyLevel::fromLevel($difficultyLevel);
    }

    /**
     * Recommend next difficulty level for progressive learning.
     */
    public function recommendNextDifficulty(
        EnhancedDifficultyLevel $currentDifficulty,
        float $currentSuccessRate,
        int $consecutiveSuccesses = 0
    ): EnhancedDifficultyLevel {
        $currentLevel = $currentDifficulty->getLevel();
        $newLevel = $currentLevel;

        // Progressive difficulty increase
        if ($currentSuccessRate >= 0.8 && $consecutiveSuccesses >= 3) {
            $newLevel = min(10, $currentLevel + 1);
        } elseif ($currentSuccessRate >= 0.9 && $consecutiveSuccesses >= 5) {
            $newLevel = min(10, $currentLevel + 2); // Accelerated progression
        } elseif ($currentSuccessRate < 0.5) {
            $newLevel = max(1, $currentLevel - 1);
        } elseif ($currentSuccessRate < 0.3) {
            $newLevel = max(1, $currentLevel - 2); // Significant step back
        }

        return EnhancedDifficultyLevel::fromLevel($newLevel);
    }

    /**
     * Calculate difficulty range for a quiz to ensure balanced challenge.
     */
    public function calculateOptimalDifficultyRange(
        EnhancedDifficultyLevel $targetDifficulty,
        int $questionCount
    ): array {
        $targetLevel = $targetDifficulty->getLevel();
        
        // For small quizzes, keep range tight
        if ($questionCount <= 5) {
            $range = 1;
        } elseif ($questionCount <= 10) {
            $range = 2;
        } else {
            $range = 3;
        }

        $minLevel = max(1, $targetLevel - $range);
        $maxLevel = min(10, $targetLevel + $range);

        return [
            'min' => EnhancedDifficultyLevel::fromLevel($minLevel),
            'max' => EnhancedDifficultyLevel::fromLevel($maxLevel),
            'target' => $targetDifficulty,
        ];
    }

    /**
     * Analyze content complexity based on text characteristics.
     */
    private function analyzeContentComplexity(EnhancedQuestion $question): int
    {
        $content = $question->getContent();
        $text = $content->getText();
        
        $complexity = 0;

        // Text length complexity
        $wordCount = str_word_count($text);
        if ($wordCount > 50) {
            $complexity += 2;
        } elseif ($wordCount > 25) {
            $complexity += 1;
        }

        // Content type complexity
        if ($content->isCode()) {
            $complexity += 3;
        } elseif ($content->isLatex()) {
            $complexity += 2;
        } elseif ($content->isMarkdown()) {
            $complexity += 1;
        }

        // Technical keyword complexity
        $technicalKeywords = ['algorithm', 'complexity', 'optimization', 'architecture', 'paradigm'];
        foreach ($technicalKeywords as $keyword) {
            if (stripos($text, $keyword) !== false) {
                $complexity += 1;
            }
        }

        return min(3, $complexity); // Cap at 3 points
    }

    /**
     * Analyze answer complexity.
     */
    private function analyzeAnswerComplexity(EnhancedQuestion $question): int
    {
        $answers = $question->getAnswers();
        $complexity = 0;

        // Number of answers
        $answerCount = count($answers);
        if ($answerCount > 6) {
            $complexity += 2;
        } elseif ($answerCount > 4) {
            $complexity += 1;
        }

        // Answer content complexity
        foreach ($answers as $answer) {
            $answerText = $answer->getContent()->getText();
            if (strlen($answerText) > 100) {
                $complexity += 1;
                break;
            }
        }

        return min(2, $complexity); // Cap at 2 points
    }

    /**
     * Analyze question type complexity.
     */
    private function analyzeQuestionTypeComplexity(EnhancedQuestion $question): int
    {
        $type = $question->getType();

        return match (true) {
            $type->isEssay() => 3,
            $type->isCodeCompletion() => 3,
            $type->isMatching() => 2,
            $type->isDragAndDrop() => 2,
            $type->isFillInTheBlank() => 2,
            $type->isMultipleChoice() => 1,
            $type->isSingleChoice() => 0,
            $type->isTrueFalse() => -1,
            default => 0,
        };
    }

    /**
     * Calculate success rate for a question from recent attempts.
     */
    private function calculateSuccessRate(EnhancedQuestion $question, array $attempts): float
    {
        $total = 0;
        $successful = 0;

        foreach ($attempts as $attempt) {
            if ($attempt instanceof EnhancedQuizAttempt) {
                $userAnswer = $attempt->getAnswerForQuestion($question->getId());
                if ($userAnswer !== null) {
                    $total++;
                    if ($userAnswer->isCorrect()) {
                        $successful++;
                    }
                }
            }
        }

        return $total > 0 ? $successful / $total : 0.5; // Default to 50% if no data
    }

    /**
     * Calculate average time spent on a question.
     */
    private function calculateAverageTime(EnhancedQuestion $question, array $attempts): ?float
    {
        $times = [];

        foreach ($attempts as $attempt) {
            if ($attempt instanceof EnhancedQuizAttempt) {
                $userAnswer = $attempt->getAnswerForQuestion($question->getId());
                if ($userAnswer !== null && $userAnswer->getTimeSpentSeconds() !== null) {
                    $times[] = $userAnswer->getTimeSpentSeconds();
                }
            }
        }

        return !empty($times) ? array_sum($times) / count($times) : null;
    }

    /**
     * Get expected time for a given difficulty level.
     */
    private function getExpectedTimeForDifficulty(int $difficultyLevel): int
    {
        // Base time expectations in seconds
        $baseTimes = [
            1 => 30,   // Very easy
            2 => 45,   // Easy
            3 => 60,   // Easy-medium
            4 => 75,   // Medium-easy
            5 => 90,   // Medium
            6 => 120,  // Medium-hard
            7 => 150,  // Hard
            8 => 180,  // Very hard
            9 => 240,  // Expert
            10 => 300, // Master
        ];

        return $baseTimes[$difficultyLevel] ?? 90;
    }
}