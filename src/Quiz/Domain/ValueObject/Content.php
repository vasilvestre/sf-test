<?php

declare(strict_types=1);

namespace App\Quiz\Domain\ValueObject;

use App\Shared\Domain\ValueObject\AbstractValueObject;

/**
 * Value object representing rich content with multimedia support.
 * Handles various content types including text, code, images, and videos.
 */
final class Content extends AbstractValueObject
{
    public const TYPE_PLAIN_TEXT = 'plain_text';
    public const TYPE_MARKDOWN = 'markdown';
    public const TYPE_HTML = 'html';
    public const TYPE_CODE = 'code';
    public const TYPE_LATEX = 'latex';

    private const VALID_TYPES = [
        self::TYPE_PLAIN_TEXT,
        self::TYPE_MARKDOWN,
        self::TYPE_HTML,
        self::TYPE_CODE,
        self::TYPE_LATEX,
    ];

    public function __construct(
        private readonly string $text,
        private readonly string $type = self::TYPE_PLAIN_TEXT,
        private readonly array $metadata = []
    ) {
        if (empty(trim($text))) {
            throw new \InvalidArgumentException('Content text cannot be empty');
        }

        if (!in_array($type, self::VALID_TYPES, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid content type "%s". Valid types are: %s', $type, implode(', ', self::VALID_TYPES))
            );
        }
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getMetadataValue(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    public function toString(): string
    {
        return $this->text;
    }

    public function isPlainText(): bool
    {
        return $this->type === self::TYPE_PLAIN_TEXT;
    }

    public function isMarkdown(): bool
    {
        return $this->type === self::TYPE_MARKDOWN;
    }

    public function isHtml(): bool
    {
        return $this->type === self::TYPE_HTML;
    }

    public function isCode(): bool
    {
        return $this->type === self::TYPE_CODE;
    }

    public function isLatex(): bool
    {
        return $this->type === self::TYPE_LATEX;
    }

    public function getLanguage(): ?string
    {
        return $this->isCode() ? $this->getMetadataValue('language') : null;
    }

    public function hasImages(): bool
    {
        return !empty($this->getMetadataValue('images', []));
    }

    public function getImages(): array
    {
        return $this->getMetadataValue('images', []);
    }

    public function hasVideos(): bool
    {
        return !empty($this->getMetadataValue('videos', []));
    }

    public function getVideos(): array
    {
        return $this->getMetadataValue('videos', []);
    }

    public function withMetadata(array $metadata): self
    {
        return new self($this->text, $this->type, array_merge($this->metadata, $metadata));
    }

    public function withType(string $type): self
    {
        return new self($this->text, $type, $this->metadata);
    }

    // Factory methods
    public static function plainText(string $text): self
    {
        return new self($text, self::TYPE_PLAIN_TEXT);
    }

    public static function markdown(string $text, array $metadata = []): self
    {
        return new self($text, self::TYPE_MARKDOWN, $metadata);
    }

    public static function html(string $text, array $metadata = []): self
    {
        return new self($text, self::TYPE_HTML, $metadata);
    }

    public static function code(string $text, string $language, array $metadata = []): self
    {
        $metadata['language'] = $language;
        return new self($text, self::TYPE_CODE, $metadata);
    }

    public static function latex(string $text, array $metadata = []): self
    {
        return new self($text, self::TYPE_LATEX, $metadata);
    }
}