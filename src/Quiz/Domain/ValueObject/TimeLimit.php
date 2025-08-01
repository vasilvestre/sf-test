<?php

declare(strict_types=1);

namespace App\Quiz\Domain\ValueObject;

use App\Shared\Domain\ValueObject\AbstractValueObject;

/**
 * Value object representing time limits with flexible constraints.
 * Supports per-question and overall quiz time limits.
 */
final class TimeLimit extends AbstractValueObject
{
    public function __construct(
        private readonly ?int $totalSeconds = null,
        private readonly ?int $perQuestionSeconds = null,
        private readonly bool $isStrict = true
    ) {
        if ($totalSeconds !== null && $totalSeconds <= 0) {
            throw new \InvalidArgumentException('Total time limit must be positive');
        }

        if ($perQuestionSeconds !== null && $perQuestionSeconds <= 0) {
            throw new \InvalidArgumentException('Per question time limit must be positive');
        }

        if ($totalSeconds === null && $perQuestionSeconds === null) {
            throw new \InvalidArgumentException('At least one time limit must be specified');
        }
    }

    public function getTotalSeconds(): ?int
    {
        return $this->totalSeconds;
    }

    public function getPerQuestionSeconds(): ?int
    {
        return $this->perQuestionSeconds;
    }

    public function isStrict(): bool
    {
        return $this->isStrict;
    }

    public function toString(): string
    {
        $parts = [];

        if ($this->totalSeconds !== null) {
            $parts[] = 'Total: ' . $this->formatDuration($this->totalSeconds);
        }

        if ($this->perQuestionSeconds !== null) {
            $parts[] = 'Per question: ' . $this->formatDuration($this->perQuestionSeconds);
        }

        $result = implode(', ', $parts);

        if (!$this->isStrict) {
            $result .= ' (flexible)';
        }

        return $result;
    }

    public function hasTotalLimit(): bool
    {
        return $this->totalSeconds !== null;
    }

    public function hasPerQuestionLimit(): bool
    {
        return $this->perQuestionSeconds !== null;
    }

    public function getTotalMinutes(): ?float
    {
        return $this->totalSeconds ? round($this->totalSeconds / 60, 1) : null;
    }

    public function getPerQuestionMinutes(): ?float
    {
        return $this->perQuestionSeconds ? round($this->perQuestionSeconds / 60, 1) : null;
    }

    public function calculateTotalForQuestions(int $questionCount): ?int
    {
        if ($this->perQuestionSeconds === null) {
            return $this->totalSeconds;
        }

        $calculatedTotal = $this->perQuestionSeconds * $questionCount;

        if ($this->totalSeconds === null) {
            return $calculatedTotal;
        }

        return min($this->totalSeconds, $calculatedTotal);
    }

    public function isTimeUp(int $elapsedSeconds, int $totalQuestions = 1, int $currentQuestion = 1): bool
    {
        if (!$this->isStrict) {
            return false;
        }

        // Check total time limit
        if ($this->totalSeconds !== null && $elapsedSeconds >= $this->totalSeconds) {
            return true;
        }

        // Check per-question time limit
        if ($this->perQuestionSeconds !== null) {
            $questionTimeLimit = $this->perQuestionSeconds * $currentQuestion;
            if ($elapsedSeconds >= $questionTimeLimit) {
                return true;
            }
        }

        return false;
    }

    public function withFlexibleMode(): self
    {
        return new self($this->totalSeconds, $this->perQuestionSeconds, false);
    }

    public function withStrictMode(): self
    {
        return new self($this->totalSeconds, $this->perQuestionSeconds, true);
    }

    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds}s";
        }

        $minutes = intval($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes < 60) {
            return $remainingSeconds > 0 ? "{$minutes}m {$remainingSeconds}s" : "{$minutes}m";
        }

        $hours = intval($minutes / 60);
        $remainingMinutes = $minutes % 60;

        $result = "{$hours}h";
        if ($remainingMinutes > 0) {
            $result .= " {$remainingMinutes}m";
        }
        if ($remainingSeconds > 0) {
            $result .= " {$remainingSeconds}s";
        }

        return $result;
    }

    // Factory methods
    public static function totalTime(int $seconds, bool $isStrict = true): self
    {
        return new self($seconds, null, $isStrict);
    }

    public static function perQuestion(int $seconds, bool $isStrict = true): self
    {
        return new self(null, $seconds, $isStrict);
    }

    public static function combined(int $totalSeconds, int $perQuestionSeconds, bool $isStrict = true): self
    {
        return new self($totalSeconds, $perQuestionSeconds, $isStrict);
    }

    public static function minutes(int $minutes, bool $isStrict = true): self
    {
        return new self($minutes * 60, null, $isStrict);
    }

    public static function hours(int $hours, bool $isStrict = true): self
    {
        return new self($hours * 3600, null, $isStrict);
    }

    public static function unlimited(): self
    {
        return new self(null, null, false);
    }
}