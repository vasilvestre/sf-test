<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Shared\Application\Service\CommandBus;
use App\Shared\Application\Service\QueryBus;
use App\Quiz\Application\Command\SubmitQuizAttemptCommand;
use App\Quiz\Application\Command\CreateQuestionCommand;
use App\Quiz\Application\Query\GetQuizQuestionsQuery;
use App\Quiz\Application\Query\GetRecommendedQuestionsQuery;
use App\Analytics\Application\Query\GetUserStatisticsQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * API controller for quiz operations using CQRS.
 */
#[Route('/api/quiz', name: 'api_quiz_')]
final class QuizController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus
    ) {
    }

    #[Route('/{id}/questions', name: 'get_questions', methods: ['GET'])]
    public function getQuestions(int $id, Request $request): JsonResponse
    {
        // Create query
        $query = new GetQuizQuestionsQuery(
            quizId: $id,
            userId: $request->query->getInt('user_id') ?: null,
            difficulty: $request->query->get('difficulty'),
            limit: $request->query->getInt('limit', 10)
        );

        try {
            // Ask query
            $questions = $this->queryBus->ask($query);

            return $this->json([
                'quiz_id' => $id,
                'questions' => $questions,
            ]);
        } catch (\Exception $exception) {
            return $this->json([
                'error' => 'Failed to fetch questions',
                'message' => $exception->getMessage(),
            ], 404);
        }
    }

    #[Route('/attempt', name: 'submit_attempt', methods: ['POST'])]
    public function submitAttempt(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Create command
        $command = new SubmitQuizAttemptCommand(
            userId: $data['user_id'],
            quizId: $data['quiz_id'],
            answers: $data['answers'],
            timeSpent: $data['time_spent'],
            startedAt: new \DateTimeImmutable($data['started_at']),
            completedAt: new \DateTimeImmutable()
        );

        try {
            // Dispatch command
            $results = $this->commandBus->dispatch($command);

            return $this->json([
                'message' => 'Quiz attempt submitted successfully',
                'results' => $results,
            ]);
        } catch (\Exception $exception) {
            return $this->json([
                'error' => 'Failed to submit quiz attempt',
                'message' => $exception->getMessage(),
            ], 400);
        }
    }

    #[Route('/questions/recommended', name: 'get_recommended', methods: ['GET'])]
    public function getRecommendedQuestions(Request $request): JsonResponse
    {
        // Create query
        $query = new GetRecommendedQuestionsQuery(
            userId: $request->query->getInt('user_id'),
            categoryId: $request->query->getInt('category_id') ?: null,
            limit: $request->query->getInt('limit', 10),
            algorithm: $request->query->get('algorithm', 'adaptive')
        );

        try {
            // Ask query
            $questions = $this->queryBus->ask($query);

            return $this->json([
                'recommended_questions' => $questions,
                'algorithm_used' => $query->algorithm,
            ]);
        } catch (\Exception $exception) {
            return $this->json([
                'error' => 'Failed to get recommended questions',
                'message' => $exception->getMessage(),
            ], 400);
        }
    }

    #[Route('/questions', name: 'create_question', methods: ['POST'])]
    public function createQuestion(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Create command
        $command = new CreateQuestionCommand(
            content: $data['content'],
            type: $data['type'],
            answers: $data['answers'],
            categoryId: $data['category_id'],
            difficulty: $data['difficulty'] ?? 'medium',
            tags: $data['tags'] ?? [],
            explanation: $data['explanation'] ?? null,
            codeExample: $data['code_example'] ?? null,
            timeLimit: $data['time_limit'] ?? null
        );

        try {
            // Dispatch command
            $questionId = $this->commandBus->dispatch($command);

            return $this->json([
                'message' => 'Question created successfully',
                'question_id' => $questionId,
            ], 201);
        } catch (\Exception $exception) {
            return $this->json([
                'error' => 'Failed to create question',
                'message' => $exception->getMessage(),
            ], 400);
        }
    }

    #[Route('/users/{userId}/statistics', name: 'get_user_statistics', methods: ['GET'])]
    public function getUserStatistics(int $userId, Request $request): JsonResponse
    {
        // Create query
        $query = new GetUserStatisticsQuery(
            userId: $userId,
            period: $request->query->get('period', 'monthly'),
            fromDate: $request->query->get('from_date') ? new \DateTimeImmutable($request->query->get('from_date')) : null,
            toDate: $request->query->get('to_date') ? new \DateTimeImmutable($request->query->get('to_date')) : null,
            includeComparisons: $request->query->getBoolean('include_comparisons')
        );

        try {
            // Ask query
            $statistics = $this->queryBus->ask($query);

            return $this->json($statistics);
        } catch (\Exception $exception) {
            return $this->json([
                'error' => 'Failed to fetch statistics',
                'message' => $exception->getMessage(),
            ], 404);
        }
    }
}