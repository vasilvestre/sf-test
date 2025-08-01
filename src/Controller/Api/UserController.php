<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Shared\Application\Service\CommandBus;
use App\Shared\Application\Service\QueryBus;
use App\User\Application\Command\RegisterUserCommand;
use App\User\Application\Command\UpdateUserProfileCommand;
use App\User\Application\Query\GetUserProfileQuery;
use App\User\Application\Query\GetUserProgressQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * API controller demonstrating CQRS usage.
 */
#[Route('/api/users', name: 'api_users_')]
final class UserController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
        private readonly ValidatorInterface $validator
    ) {
    }

    #[Route('', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Create command
        $command = new RegisterUserCommand(
            email: $data['email'],
            username: $data['username'],
            plainPassword: $data['password'],
            role: $data['role'] ?? 'ROLE_STUDENT'
        );

        // Validate command (you could create a validator for this)
        $violations = $this->validator->validate($command);
        if (count($violations) > 0) {
            return $this->json(['errors' => (string) $violations], 400);
        }

        try {
            // Dispatch command
            $userId = $this->commandBus->dispatch($command);

            return $this->json([
                'message' => 'User registered successfully',
                'user_id' => $userId,
            ], 201);
        } catch (\Exception $exception) {
            return $this->json([
                'error' => 'Registration failed',
                'message' => $exception->getMessage(),
            ], 400);
        }
    }

    #[Route('/{id}/profile', name: 'get_profile', methods: ['GET'])]
    public function getProfile(int $id, Request $request): JsonResponse
    {
        // Create query
        $query = new GetUserProfileQuery(
            userId: $id,
            includeAchievements: $request->query->getBoolean('include_achievements'),
            includePreferences: $request->query->getBoolean('include_preferences')
        );

        try {
            // Ask query
            $profile = $this->queryBus->ask($query);

            return $this->json($profile);
        } catch (\Exception $exception) {
            return $this->json([
                'error' => 'Failed to fetch profile',
                'message' => $exception->getMessage(),
            ], 404);
        }
    }

    #[Route('/{id}/profile', name: 'update_profile', methods: ['PUT'])]
    public function updateProfile(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Create command
        $command = new UpdateUserProfileCommand(
            userId: $id,
            username: $data['username'] ?? null,
            firstName: $data['first_name'] ?? null,
            lastName: $data['last_name'] ?? null,
            bio: $data['bio'] ?? null,
            avatar: $data['avatar'] ?? null
        );

        try {
            // Dispatch command
            $this->commandBus->dispatch($command);

            return $this->json([
                'message' => 'Profile updated successfully',
            ]);
        } catch (\Exception $exception) {
            return $this->json([
                'error' => 'Profile update failed',
                'message' => $exception->getMessage(),
            ], 400);
        }
    }

    #[Route('/{id}/progress', name: 'get_progress', methods: ['GET'])]
    public function getProgress(int $id, Request $request): JsonResponse
    {
        // Create query
        $query = new GetUserProgressQuery(
            userId: $id,
            categoryId: $request->query->getInt('category_id') ?: null,
            period: $request->query->get('period')
        );

        try {
            // Ask query
            $progress = $this->queryBus->ask($query);

            return $this->json($progress);
        } catch (\Exception $exception) {
            return $this->json([
                'error' => 'Failed to fetch progress',
                'message' => $exception->getMessage(),
            ], 404);
        }
    }
}