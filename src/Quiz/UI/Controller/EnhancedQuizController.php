<?php

declare(strict_types=1);

namespace App\Quiz\UI\Controller;

use App\Quiz\Application\Command\StartQuizSessionCommand;
use App\Quiz\Application\Command\SubmitQuestionAnswerCommand;
use App\Quiz\Application\Command\CompleteQuizSessionCommand;
use App\Quiz\Application\Query\GetActiveQuizSessionQuery;
use App\Quiz\Application\Query\GetQuizSessionDetailsQuery;
use App\Quiz\Application\Query\GetQuizSessionAnalyticsQuery;
use App\Quiz\Application\Query\GetAdaptiveLearningDataQuery;
use App\Quiz\Domain\ValueObject\EnhancedDifficultyLevel;
use App\Quiz\Domain\ValueObject\TimeLimit;
use App\Shared\Application\Service\CommandBus;
use App\Shared\Application\Service\QueryBus;
use App\User\Domain\Entity\UserId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Enhanced quiz controller with advanced quiz engine features.
 * Supports adaptive learning, multiple question types, and real-time analytics.
 */
#[Route('/api/quiz/v2')]
#[IsGranted('ROLE_USER')]
final class EnhancedQuizController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus
    ) {
    }

    /**
     * Start a new quiz session with intelligent question selection.
     */
    #[Route('/session/start', name: 'quiz_v2_start_session', methods: ['POST'])]
    public function startSession(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        // Get current user
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated'], 401);
        }

        try {
            // Parse request parameters
            $categoryId = $data['categoryId'] ?? null;
            $targetDifficulty = isset($data['difficulty']) 
                ? new EnhancedDifficultyLevel($data['difficulty'])
                : null;
            $questionCount = $data['questionCount'] ?? 15;
            $timeLimit = isset($data['timeLimit']) 
                ? new TimeLimit($data['timeLimit'])
                : null;
            $adaptiveLearning = $data['adaptiveLearning'] ?? true;
            $questionTypes = $data['questionTypes'] ?? [];
            $tags = $data['tags'] ?? [];
            $practiceMode = $data['practiceMode'] ?? false;

            // Create and dispatch command
            $command = new StartQuizSessionCommand(
                new UserId($user->getId()),
                $categoryId,
                $targetDifficulty,
                $questionCount,
                $timeLimit,
                $adaptiveLearning,
                $questionTypes,
                $tags,
                $practiceMode
            );

            $sessionId = $this->commandBus->dispatch($command);

            // Get session details
            $sessionQuery = new GetQuizSessionDetailsQuery($sessionId);
            $sessionDetails = $this->queryBus->ask($sessionQuery);

            return new JsonResponse([
                'success' => true,
                'sessionId' => $sessionId,
                'session' => $sessionDetails->toArray()
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Failed to start quiz session',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get the current active quiz session for the user.
     */
    #[Route('/session/active', name: 'quiz_v2_active_session', methods: ['GET'])]
    public function getActiveSession(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated'], 401);
        }

        try {
            $query = new GetActiveQuizSessionQuery(new UserId($user->getId()));
            $activeSession = $this->queryBus->ask($query);

            if (!$activeSession) {
                return new JsonResponse(['activeSession' => null]);
            }

            return new JsonResponse([
                'activeSession' => $activeSession->toArray()
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Failed to get active session',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detailed information about a quiz session.
     */
    #[Route('/session/{sessionId}', name: 'quiz_v2_session_details', methods: ['GET'])]
    public function getSessionDetails(string $sessionId): JsonResponse
    {
        try {
            $query = new GetQuizSessionDetailsQuery($sessionId);
            $sessionDetails = $this->queryBus->ask($query);

            return new JsonResponse([
                'session' => $sessionDetails->toArray()
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Failed to get session details',
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Submit an answer for a question in the active session.
     */
    #[Route('/session/{sessionId}/answer', name: 'quiz_v2_submit_answer', methods: ['POST'])]
    public function submitAnswer(string $sessionId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            // Validate required fields
            if (!isset($data['questionId']) || !isset($data['answers'])) {
                return new JsonResponse([
                    'error' => 'Missing required fields: questionId, answers'
                ], 400);
            }

            // Create and dispatch command
            $command = new SubmitQuestionAnswerCommand(
                $sessionId,
                $data['questionId'],
                $data['answers'],
                $data['timeSpent'] ?? 0.0,
                $data['metadata'] ?? null
            );

            $result = $this->commandBus->dispatch($command);

            return new JsonResponse([
                'success' => true,
                'result' => $result
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Failed to submit answer',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Complete the quiz session and get final results.
     */
    #[Route('/session/{sessionId}/complete', name: 'quiz_v2_complete_session', methods: ['POST'])]
    public function completeSession(string $sessionId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            // Create and dispatch command
            $command = new CompleteQuizSessionCommand(
                $sessionId,
                $data['totalTimeSpent'] ?? 0.0
            );

            $result = $this->commandBus->dispatch($command);

            // Get final analytics
            $analyticsQuery = new GetQuizSessionAnalyticsQuery($sessionId);
            $analytics = $this->queryBus->ask($analyticsQuery);

            return new JsonResponse([
                'success' => true,
                'result' => $result,
                'analytics' => $analytics->toArray()
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Failed to complete session',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get analytics for a completed quiz session.
     */
    #[Route('/session/{sessionId}/analytics', name: 'quiz_v2_session_analytics', methods: ['GET'])]
    public function getSessionAnalytics(string $sessionId): JsonResponse
    {
        try {
            $query = new GetQuizSessionAnalyticsQuery($sessionId);
            $analytics = $this->queryBus->ask($query);

            return new JsonResponse([
                'analytics' => $analytics->toArray()
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Failed to get session analytics',
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get adaptive learning recommendations for the user.
     */
    #[Route('/recommendations', name: 'quiz_v2_recommendations', methods: ['GET'])]
    public function getRecommendations(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated'], 401);
        }

        try {
            $strategy = $request->query->get('strategy', 'adaptive');
            $limit = (int) $request->query->get('limit', 10);
            $categoryId = $request->query->get('categoryId');

            $query = new GetAdaptiveLearningDataQuery(
                new UserId($user->getId()),
                $strategy,
                $limit,
                $categoryId
            );

            $recommendations = $this->queryBus->ask($query);

            return new JsonResponse([
                'recommendations' => $recommendations->toArray()
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Failed to get recommendations',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get next recommended question in adaptive mode.
     */
    #[Route('/session/{sessionId}/next-question', name: 'quiz_v2_next_question', methods: ['GET'])]
    public function getNextQuestion(string $sessionId): JsonResponse
    {
        try {
            $query = new GetQuizSessionDetailsQuery($sessionId);
            $session = $this->queryBus->ask($query);

            $currentQuestion = $session->getCurrentQuestion();

            if (!$currentQuestion) {
                return new JsonResponse([
                    'question' => null,
                    'message' => 'No more questions available'
                ]);
            }

            return new JsonResponse([
                'question' => [
                    'id' => $currentQuestion['id'],
                    'type' => $currentQuestion['type'],
                    'content' => $currentQuestion['content'],
                    'answers' => $currentQuestion['answers'],
                    'difficulty' => $currentQuestion['difficulty'],
                    'timeLimit' => $currentQuestion['timeLimit'] ?? null,
                    'metadata' => $currentQuestion['metadata'] ?? []
                ],
                'progress' => $session->getProgress(),
                'questionNumber' => $session->getCurrentQuestionIndex() + 1,
                'totalQuestions' => $session->getTotalQuestions()
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Failed to get next question',
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Pause/resume a quiz session.
     */
    #[Route('/session/{sessionId}/pause', name: 'quiz_v2_pause_session', methods: ['POST'])]
    public function pauseSession(string $sessionId): JsonResponse
    {
        // Implementation would involve adding pause/resume functionality to the domain
        return new JsonResponse([
            'message' => 'Pause/resume functionality coming soon'
        ]);
    }

    /**
     * Get quiz session statistics.
     */
    #[Route('/stats', name: 'quiz_v2_stats', methods: ['GET'])]
    public function getStats(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated'], 401);
        }

        try {
            // This could be enhanced with specific stats queries
            $query = new GetAdaptiveLearningDataQuery(
                new UserId($user->getId()),
                'performance',
                100
            );

            $learningData = $this->queryBus->ask($query);

            return new JsonResponse([
                'stats' => [
                    'totalSessions' => count($learningData->getHistoricalPerformance()),
                    'averageScore' => $learningData->getOverallMetrics()['averageScore'] ?? 0,
                    'totalTimeSpent' => $learningData->getOverallMetrics()['totalTimeSpent'] ?? 0,
                    'strongestAreas' => $learningData->getOverallMetrics()['strongestAreas'] ?? [],
                    'improvementAreas' => $learningData->getOverallMetrics()['improvementAreas'] ?? []
                ]
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Failed to get stats',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}