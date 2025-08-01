<?php

declare(strict_types=1);

namespace App\Shared\Application\Command;

/**
 * Command to send an email.
 */
final class SendEmailCommand implements CommandInterface
{
    public function __construct(
        public readonly string $to,
        public readonly string $subject,
        public readonly string $template,
        public readonly array $templateData = [],
        public readonly ?string $from = null,
        public readonly array $attachments = [],
        public readonly string $priority = 'normal' // high, normal, low
    ) {
    }
}