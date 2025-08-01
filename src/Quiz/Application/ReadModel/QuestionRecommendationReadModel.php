<?php

declare(strict_types=1);

namespace App\Quiz\Application\ReadModel;

/**
 * Read model for question recommendations.
 * Provides personalized question suggestions based on adaptive learning algorithms.
 */
final class QuestionRecommendationReadModel
{
    public function __construct(
        public readonly int $userId,
        public readonly string $sessionId,
        public readonly array $recommendedQuestions,
        public readonly array $algorithmMetadata,
        public readonly string $recommendationStrategy,
        public readonly float $confidenceScore,
        public readonly array $reasoningFactors,
        public readonly array $alternativeQuestions,
        public readonly array $difficultyDistribution,
        public readonly array $categoryDistribution,
        public readonly int $totalRecommendations,
        public readonly ?\DateTimeImmutable $generatedAt
    ) {
    }

    /**
     * Get the next recommended question.
     */
    public function getNextQuestion(): ?array
    {
        return $this->recommendedQuestions[0] ?? null;
    }

    /**
     * Get questions filtered by difficulty level.
     */
    public function getQuestionsByDifficulty(string $difficulty): array
    {
        return array_filter($this->recommendedQuestions, function ($question) use ($difficulty) {
            return $question['difficulty'] === $difficulty;
        });
    }

    /**
     * Get questions filtered by category.
     */
    public function getQuestionsByCategory(string $category): array
    {
        return array_filter($this->recommendedQuestions, function ($question) use ($category) {
            return $question['category'] === $category;
        });
    }

    /**
     * Get primary reasoning for recommendations.
     */
    public function getPrimaryReasoning(): string
    {
        if (empty($this->reasoningFactors)) {
            return 'General recommendation';
        }

        $primary = array_reduce($this->reasoningFactors, function ($max, $factor) {
            return ($factor['weight'] > ($max['weight'] ?? 0)) ? $factor : $max;
        }, []);

        return $primary['reason'] ?? 'Adaptive learning algorithm';
    }

    /**
     * Check if recommendations are highly confident.
     */
    public function isHighConfidence(): bool
    {
        return $this->confidenceScore >= 0.8;
    }

    /**
     * Get distribution summary for UI display.
     */
    public function getDistributionSummary(): array
    {
        return [
            'difficulty' => $this->difficultyDistribution,
            'category' => $this->categoryDistribution,
            'totalQuestions' => $this->totalRecommendations,
            'strategy' => $this->recommendationStrategy,
        ];
    }

    /**
     * Get alternative questions as fallback options.
     */
    public function getFallbackQuestions(int $count = 3): array
    {
        return array_slice($this->alternativeQuestions, 0, $count);
    }

    /**
     * Get recommendation explanation for the user.
     */
    public function getExplanation(): array
    {
        $explanation = [
            'strategy' => $this->getStrategyDescription(),
            'reasoning' => $this->getPrimaryReasoning(),
            'confidence' => $this->getConfidenceDescription(),
            'factors' => array_map(function ($factor) {
                return [
                    'factor' => $factor['factor'],
                    'reason' => $factor['reason'],
                    'impact' => $this->getImpactDescription($factor['weight']),
                ];
            }, $this->reasoningFactors),
        ];

        return $explanation;
    }

    /**
     * Get human-readable strategy description.
     */
    private function getStrategyDescription(): string
    {
        return match ($this->recommendationStrategy) {
            'adaptive_difficulty' => 'Questions adapted to your current skill level',
            'knowledge_gap' => 'Questions targeting your areas for improvement',
            'spaced_repetition' => 'Questions based on spaced repetition learning',
            'category_balance' => 'Questions balanced across different topics',
            'performance_based' => 'Questions based on your recent performance',
            'mixed_strategy' => 'Questions using multiple recommendation factors',
            default => 'Personalized question selection',
        };
    }

    /**
     * Get confidence level description.
     */
    private function getConfidenceDescription(): string
    {
        return match (true) {
            $this->confidenceScore >= 0.9 => 'Very high confidence - strong data support',
            $this->confidenceScore >= 0.8 => 'High confidence - good data support',
            $this->confidenceScore >= 0.6 => 'Medium confidence - moderate data support',
            $this->confidenceScore >= 0.4 => 'Low confidence - limited data support',
            default => 'Very low confidence - insufficient data',
        };
    }

    /**
     * Get impact description for reasoning factors.
     */
    private function getImpactDescription(float $weight): string
    {
        return match (true) {
            $weight >= 0.8 => 'Major impact',
            $weight >= 0.6 => 'Significant impact',
            $weight >= 0.4 => 'Moderate impact',
            $weight >= 0.2 => 'Minor impact',
            default => 'Minimal impact',
        };
    }

    /**
     * Get metadata for algorithm debugging and tuning.
     */
    public function getAlgorithmInsights(): array
    {
        return [
            'strategy' => $this->recommendationStrategy,
            'confidence' => $this->confidenceScore,
            'metadata' => $this->algorithmMetadata,
            'factors' => $this->reasoningFactors,
            'distributions' => [
                'difficulty' => $this->difficultyDistribution,
                'category' => $this->categoryDistribution,
            ],
            'generatedAt' => $this->generatedAt?->format('c'),
        ];
    }

    /**
     * Convert to array for API responses.
     */
    public function toArray(): array
    {
        return [
            'userId' => $this->userId,
            'sessionId' => $this->sessionId,
            'recommendedQuestions' => $this->recommendedQuestions,
            'nextQuestion' => $this->getNextQuestion(),
            'strategy' => $this->recommendationStrategy,
            'strategyDescription' => $this->getStrategyDescription(),
            'confidenceScore' => $this->confidenceScore,
            'confidenceDescription' => $this->getConfidenceDescription(),
            'isHighConfidence' => $this->isHighConfidence(),
            'primaryReasoning' => $this->getPrimaryReasoning(),
            'explanation' => $this->getExplanation(),
            'distributionSummary' => $this->getDistributionSummary(),
            'fallbackQuestions' => $this->getFallbackQuestions(),
            'algorithmInsights' => $this->getAlgorithmInsights(),
            'totalRecommendations' => $this->totalRecommendations,
            'generatedAt' => $this->generatedAt?->format('c'),
        ];
    }
}