<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\User\Application\Command\RegisterUserCommand;
use App\User\Application\Command\VerifyUserEmailCommand;
use App\User\Domain\Exception\UserAlreadyExistsException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Controller for user registration and authentication.
 */
#[Route('/auth', name: 'auth_')]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $commandBus
    ) {
    }

    #[Route('/login', name: 'login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_quiz_index');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('user/auth/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/register', name: 'register')]
    public function register(Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_quiz_index');
        }

        if ($request->isMethod('POST')) {
            try {
                $command = new RegisterUserCommand(
                    email: $request->request->get('email'),
                    username: $request->request->get('username'),
                    plainPassword: $request->request->get('password'),
                    role: $request->request->get('role', 'ROLE_STUDENT')
                );

                $this->commandBus->dispatch($command);

                $this->addFlash('success', 'Registration successful! Please check your email to verify your account.');
                return $this->redirectToRoute('auth_login');

            } catch (UserAlreadyExistsException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', 'Invalid input: ' . $e->getMessage());
            }
        }

        return $this->render('user/auth/register.html.twig');
    }

    #[Route('/verify-email/{userId}', name: 'verify_email')]
    public function verifyEmail(int $userId): Response
    {
        try {
            $command = new VerifyUserEmailCommand($userId);
            $this->commandBus->dispatch($command);

            $this->addFlash('success', 'Email verified successfully! You can now use all features.');
            return $this->redirectToRoute('auth_login');

        } catch (\Exception $e) {
            $this->addFlash('error', 'Email verification failed: ' . $e->getMessage());
            return $this->redirectToRoute('auth_login');
        }
    }
}