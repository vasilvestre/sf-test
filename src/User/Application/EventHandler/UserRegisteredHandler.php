<?php

declare(strict_types=1);

namespace App\User\Application\EventHandler;

use App\User\Domain\Event\UserRegistered;
use App\Shared\Application\Command\SendEmailCommand;
use Symfony\Component\Messenger\MessageBusInterface;
use Psr\Log\LoggerInterface;

/**
 * Event handler for user registration events.
 */
final class UserRegisteredHandler
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(UserRegistered $event): void
    {
        $this->logger->info('Processing UserRegistered event', [
            'user_id' => $event->getUserId(),
            'email' => $event->getEmail(),
        ]);

        // Send welcome email
        $this->sendWelcomeEmail($event);
        
        // Create default user preferences
        $this->createDefaultPreferences($event);
        
        // Record user registration analytics
        $this->recordRegistrationAnalytics($event);
    }

    private function sendWelcomeEmail(UserRegistered $event): void
    {
        $sendEmailCommand = new SendEmailCommand(
            to: $event->getEmail(),
            subject: 'Welcome to Quiz Application!',
            template: 'user/welcome',
            templateData: [
                'username' => $event->getUsername(),
                'email' => $event->getEmail(),
            ],
            priority: 'high'
        );

        $this->commandBus->dispatch($sendEmailCommand);
    }

    private function createDefaultPreferences(UserRegistered $event): void
    {
        // Implementation would create default user preferences
        $this->logger->debug('Creating default preferences for user', [
            'user_id' => $event->getUserId(),
        ]);
    }

    private function recordRegistrationAnalytics(UserRegistered $event): void
    {
        // Implementation would record registration analytics
        $this->logger->debug('Recording registration analytics', [
            'user_id' => $event->getUserId(),
        ]);
    }
}