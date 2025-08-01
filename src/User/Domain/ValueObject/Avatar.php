<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

use App\Shared\Domain\ValueObject\AbstractValueObject;

/**
 * Value object representing user avatar information.
 */
final class Avatar extends AbstractValueObject
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB

    public function __construct(
        private readonly ?string $path = null,
        private readonly ?string $originalName = null,
        private readonly ?int $size = null
    ) {
        if ($path !== null) {
            $this->validate($path, $originalName, $size);
        }
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getValue(): ?string
    {
        return $this->path;
    }

    public function toString(): string
    {
        return $this->path ?? '';
    }

    public static function none(): self
    {
        return new self();
    }

    public static function fromPath(string $path, ?string $originalName = null, ?int $size = null): self
    {
        return new self($path, $originalName, $size);
    }

    public function hasAvatar(): bool
    {
        return $this->path !== null;
    }

    public function getUrl(string $baseUrl = '/uploads/avatars'): string
    {
        if (!$this->hasAvatar()) {
            return '/images/default-avatar.png';
        }

        return $baseUrl . '/' . $this->path;
    }

    public function getExtension(): ?string
    {
        if (!$this->hasAvatar()) {
            return null;
        }

        return strtolower(pathinfo($this->path, PATHINFO_EXTENSION));
    }

    private function validate(?string $path, ?string $originalName, ?int $size): void
    {
        if ($path === null) {
            return;
        }

        if (empty($path)) {
            throw new \InvalidArgumentException('Avatar path cannot be empty');
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid avatar file extension "%s". Allowed extensions: %s',
                    $extension,
                    implode(', ', self::ALLOWED_EXTENSIONS)
                )
            );
        }

        if ($size !== null && $size > self::MAX_FILE_SIZE) {
            throw new \InvalidArgumentException(
                sprintf('Avatar file size cannot exceed %d bytes', self::MAX_FILE_SIZE)
            );
        }
    }
}