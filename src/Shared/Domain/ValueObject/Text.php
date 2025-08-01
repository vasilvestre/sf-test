<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

/**
 * Value object representing text content.
 */
final class Text extends AbstractValueObject
{
    public function __construct(
        private readonly string $value
    ) {
        if (trim($value) === '') {
            throw new \InvalidArgumentException('Text cannot be empty');
        }
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function getLength(): int
    {
        return mb_strlen($this->value);
    }

    public function truncate(int $maxLength, string $suffix = '...'): self
    {
        if ($this->getLength() <= $maxLength) {
            return $this;
        }

        $truncated = mb_substr($this->value, 0, $maxLength - mb_strlen($suffix)) . $suffix;
        return new self($truncated);
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }
}