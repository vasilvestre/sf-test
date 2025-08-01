<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\User\Application\Command\ChangeUserPasswordCommand;
use App\User\Application\Command\UpdateUserPreferencesCommand;
use App\User\Application\Command\UpdateUserProfileCommand;
use App\User\Application\Query\GetUserByIdQuery;
use App\User\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for user profile management.
 */
#[Route('/profile', name: 'profile_')]
#[IsGranted('ROLE_STUDENT')]
class ProfileController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly MessageBusInterface $queryBus
    ) {
    }

    #[Route('/', name: 'index')]
    public function index(): Response
    {
        /** @var SecurityUser $user */
        $user = $this->getUser();
        
        $query = new GetUserByIdQuery($user->getUserId()->getValue());
        $envelope = $this->queryBus->dispatch($query);
        $domainUser = $envelope->last(HandledStamp::class)->getResult();

        return $this->render('user/profile/index.html.twig', [
            'user' => $domainUser,
        ]);
    }

    #[Route('/edit', name: 'edit')]
    public function edit(Request $request): Response
    {
        /** @var SecurityUser $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            try {
                $dateOfBirth = null;
                if ($request->request->get('date_of_birth')) {
                    $dateOfBirth = new \DateTimeImmutable($request->request->get('date_of_birth'));
                }

                $command = new UpdateUserProfileCommand(
                    userId: $user->getUserId()->getValue(),
                    firstName: $request->request->get('first_name'),
                    lastName: $request->request->get('last_name'),
                    bio: $request->request->get('bio'),
                    dateOfBirth: $dateOfBirth
                );

                $this->commandBus->dispatch($command);

                $this->addFlash('success', 'Profile updated successfully!');
                return $this->redirectToRoute('profile_index');

            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', 'Invalid input: ' . $e->getMessage());
            }
        }

        $query = new GetUserByIdQuery($user->getUserId()->getValue());
        $envelope = $this->queryBus->dispatch($query);
        $domainUser = $envelope->last(HandledStamp::class)->getResult();

        return $this->render('user/profile/edit.html.twig', [
            'user' => $domainUser,
        ]);
    }

    #[Route('/preferences', name: 'preferences')]
    public function preferences(Request $request): Response
    {
        /** @var SecurityUser $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            try {
                $preferences = [
                    'preferred_difficulty' => $request->request->get('preferred_difficulty'),
                    'theme' => $request->request->get('theme'),
                    'notifications_enabled' => (bool) $request->request->get('notifications_enabled'),
                    'email_notifications' => (bool) $request->request->get('email_notifications'),
                    'achievement_notifications' => (bool) $request->request->get('achievement_notifications'),
                    'language' => $request->request->get('language'),
                    'auto_advance' => (bool) $request->request->get('auto_advance'),
                    'questions_per_session' => (int) $request->request->get('questions_per_session'),
                ];

                $command = new UpdateUserPreferencesCommand(
                    userId: $user->getUserId()->getValue(),
                    preferences: $preferences
                );

                $this->commandBus->dispatch($command);

                $this->addFlash('success', 'Preferences updated successfully!');
                return $this->redirectToRoute('profile_preferences');

            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', 'Invalid input: ' . $e->getMessage());
            }
        }

        $query = new GetUserByIdQuery($user->getUserId()->getValue());
        $envelope = $this->queryBus->dispatch($query);
        $domainUser = $envelope->last(HandledStamp::class)->getResult();

        return $this->render('user/profile/preferences.html.twig', [
            'user' => $domainUser,
        ]);
    }

    #[Route('/change-password', name: 'change_password')]
    public function changePassword(Request $request): Response
    {
        /** @var SecurityUser $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            try {
                $command = new ChangeUserPasswordCommand(
                    userId: $user->getUserId()->getValue(),
                    currentPassword: $request->request->get('current_password'),
                    newPassword: $request->request->get('new_password')
                );

                $this->commandBus->dispatch($command);

                $this->addFlash('success', 'Password changed successfully!');
                return $this->redirectToRoute('profile_index');

            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('user/profile/change_password.html.twig');
    }
}