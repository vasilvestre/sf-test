<?php

declare(strict_types=1);

namespace App\Shared\Application\Handler;

use App\Shared\Application\Command\SendEmailCommand;
use App\Shared\Application\Command\CommandHandlerInterface;
use App\Shared\Application\Command\CommandInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;
use Psr\Log\LoggerInterface;

/**
 * Handler for sending emails.
 */
final class SendEmailCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly Environment $twig,
        private readonly LoggerInterface $logger,
        private readonly string $defaultFromEmail = 'noreply@quiz-app.com'
    ) {
    }

    public function handle(CommandInterface $command): mixed
    {
        if (!$command instanceof SendEmailCommand) {
            throw new \InvalidArgumentException('Expected SendEmailCommand');
        }
        
        $this->__invoke($command);
        
        return null;
    }

    public function __invoke(SendEmailCommand $command): void
    {
        try {
            $email = $this->createEmail($command);
            $this->mailer->send($email);
            
            $this->logger->info('Email sent successfully', [
                'to' => $command->to,
                'subject' => $command->subject,
                'template' => $command->template,
                'priority' => $command->priority,
            ]);
        } catch (\Throwable $exception) {
            $this->logger->error('Failed to send email', [
                'to' => $command->to,
                'subject' => $command->subject,
                'template' => $command->template,
                'error' => $exception->getMessage(),
            ]);
            
            throw $exception;
        }
    }

    private function createEmail(SendEmailCommand $command): Email
    {
        $htmlContent = $this->twig->render(
            "emails/{$command->template}.html.twig",
            $command->templateData
        );

        $textContent = $this->twig->render(
            "emails/{$command->template}.txt.twig",
            $command->templateData
        );

        $email = (new Email())
            ->from($command->from ?? $this->defaultFromEmail)
            ->to($command->to)
            ->subject($command->subject)
            ->html($htmlContent)
            ->text($textContent);

        // Set priority
        match ($command->priority) {
            'high' => $email->priority(Email::PRIORITY_HIGH),
            'low' => $email->priority(Email::PRIORITY_LOW),
            default => $email->priority(Email::PRIORITY_NORMAL),
        };

        // Add attachments
        foreach ($command->attachments as $attachment) {
            $email->attachFromPath($attachment['path'], $attachment['name'] ?? null);
        }

        return $email;
    }
}