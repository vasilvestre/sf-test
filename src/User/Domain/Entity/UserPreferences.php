<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\Quiz\Domain\ValueObject\DifficultyLevel;
use App\Shared\Domain\ValueObject\Id;
use Doctrine\ORM\Mapping as ORM;

/**
 * User preferences entity for learning settings and UI preferences.
 */
#[ORM\Embeddable]
class UserPreferences
{
    #[ORM\Column(type: 'integer')]
    private int $userId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $preferredDifficulty;

    #[ORM\Column(type: 'string', length: 10)]
    private string $theme;

    #[ORM\Column(type: 'boolean')]
    private bool $notificationsEnabled;

    #[ORM\Column(type: 'boolean')]
    private bool $emailNotifications;

    #[ORM\Column(type: 'boolean')]
    private bool $achievementNotifications;

    #[ORM\Column(type: 'string', length: 5)]
    private string $language;

    #[ORM\Column(type: 'boolean')]
    private bool $autoAdvance;

    #[ORM\Column(type: 'integer')]
    private int $questionsPerSession;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        Id $userId,
        ?DifficultyLevel $preferredDifficulty = null,
        string $theme = 'light',
        bool $notificationsEnabled = true,
        bool $emailNotifications = true,
        bool $achievementNotifications = true,
        string $language = 'en',
        bool $autoAdvance = false,
        int $questionsPerSession = 10,
        ?\DateTimeImmutable $updatedAt = null
    ) {
        $this->userId = $userId->getValue();
        $this->preferredDifficulty = ($preferredDifficulty ?? DifficultyLevel::intermediate())->getValue();
        $this->theme = $theme;
        $this->notificationsEnabled = $notificationsEnabled;
        $this->emailNotifications = $emailNotifications;
        $this->achievementNotifications = $achievementNotifications;
        $this->language = $language;
        $this->autoAdvance = $autoAdvance;
        $this->questionsPerSession = $questionsPerSession;
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable();
    }

    public function getUserId(): Id
    {
        return Id::fromInt($this->userId);
    }

    public function getPreferredDifficulty(): DifficultyLevel
    {
        return DifficultyLevel::fromString($this->preferredDifficulty);
    }

    public function getTheme(): string
    {
        return $this->theme;
    }

    public function isNotificationsEnabled(): bool
    {
        return $this->notificationsEnabled;
    }

    public function isEmailNotificationsEnabled(): bool
    {
        return $this->emailNotifications;
    }

    public function isAchievementNotificationsEnabled(): bool
    {
        return $this->achievementNotifications;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function isAutoAdvanceEnabled(): bool
    {
        return $this->autoAdvance;
    }

    public function getQuestionsPerSession(): int
    {
        return $this->questionsPerSession;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @param array<string, mixed> $preferences
     */
    public function update(array $preferences): void
    {
        $hasChanges = false;

        if (isset($preferences['preferred_difficulty'])) {
            $difficulty = $preferences['preferred_difficulty'];
            if (is_string($difficulty)) {
                $newDifficulty = DifficultyLevel::fromString($difficulty);
                if ($this->preferredDifficulty !== $newDifficulty->getValue()) {
                    $this->preferredDifficulty = $newDifficulty->getValue();
                    $hasChanges = true;
                }
            }
        }

        if (isset($preferences['theme'])) {
            $theme = $this->validateTheme($preferences['theme']);
            if ($theme !== $this->theme) {
                $this->theme = $theme;
                $hasChanges = true;
            }
        }

        if (isset($preferences['notifications_enabled'])) {
            $notificationsEnabled = (bool) $preferences['notifications_enabled'];
            if ($notificationsEnabled !== $this->notificationsEnabled) {
                $this->notificationsEnabled = $notificationsEnabled;
                $hasChanges = true;
            }
        }

        if (isset($preferences['email_notifications'])) {
            $emailNotifications = (bool) $preferences['email_notifications'];
            if ($emailNotifications !== $this->emailNotifications) {
                $this->emailNotifications = $emailNotifications;
                $hasChanges = true;
            }
        }

        if (isset($preferences['achievement_notifications'])) {
            $achievementNotifications = (bool) $preferences['achievement_notifications'];
            if ($achievementNotifications !== $this->achievementNotifications) {
                $this->achievementNotifications = $achievementNotifications;
                $hasChanges = true;
            }
        }

        if (isset($preferences['language'])) {
            $language = $this->validateLanguage($preferences['language']);
            if ($language !== $this->language) {
                $this->language = $language;
                $hasChanges = true;
            }
        }

        if (isset($preferences['auto_advance'])) {
            $autoAdvance = (bool) $preferences['auto_advance'];
            if ($autoAdvance !== $this->autoAdvance) {
                $this->autoAdvance = $autoAdvance;
                $hasChanges = true;
            }
        }

        if (isset($preferences['questions_per_session'])) {
            $questionsPerSession = $this->validateQuestionsPerSession($preferences['questions_per_session']);
            if ($questionsPerSession !== $this->questionsPerSession) {
                $this->questionsPerSession = $questionsPerSession;
                $hasChanges = true;
            }
        }

        if ($hasChanges) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    private function validateTheme(mixed $theme): string
    {
        $validThemes = ['light', 'dark', 'auto'];
        
        if (!is_string($theme) || !in_array($theme, $validThemes, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid theme "%s". Valid themes: %s', $theme, implode(', ', $validThemes))
            );
        }

        return $theme;
    }

    private function validateLanguage(mixed $language): string
    {
        $validLanguages = ['en', 'fr', 'es', 'de', 'it'];
        
        if (!is_string($language) || !in_array($language, $validLanguages, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid language "%s". Valid languages: %s', $language, implode(', ', $validLanguages))
            );
        }

        return $language;
    }

    private function validateQuestionsPerSession(mixed $questionsPerSession): int
    {
        if (!is_int($questionsPerSession) && !is_numeric($questionsPerSession)) {
            throw new \InvalidArgumentException('Questions per session must be a number');
        }

        $questions = (int) $questionsPerSession;

        if ($questions < 5 || $questions > 50) {
            throw new \InvalidArgumentException('Questions per session must be between 5 and 50');
        }

        return $questions;
    }
}