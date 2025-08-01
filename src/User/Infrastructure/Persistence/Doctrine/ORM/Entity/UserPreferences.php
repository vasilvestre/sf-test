<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence\Doctrine\ORM\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'user_preferences')]
class UserPreferences
{
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'preferences')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: Types::INTEGER, name: 'difficulty_preference', options: ['default' => 5])]
    private int $difficultyPreference = 5;

    #[ORM\Column(type: Types::STRING, length: 20, options: ['default' => 'light'])]
    private string $theme = 'light';

    #[ORM\Column(type: Types::BOOLEAN, name: 'notifications_enabled', options: ['default' => true])]
    private bool $notificationsEnabled = true;

    #[ORM\Column(type: Types::BOOLEAN, name: 'auto_advance', options: ['default' => false])]
    private bool $autoAdvance = false;

    #[ORM\Column(type: Types::BOOLEAN, name: 'show_explanations', options: ['default' => true])]
    private bool $showExplanations = true;

    #[ORM\Column(type: Types::BOOLEAN, name: 'sound_enabled', options: ['default' => true])]
    private bool $soundEnabled = true;

    #[ORM\Column(type: Types::JSON, name: 'preferences_json', nullable: true)]
    private ?array $preferencesJson = null;

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getDifficultyPreference(): int
    {
        return $this->difficultyPreference;
    }

    public function setDifficultyPreference(int $difficultyPreference): self
    {
        $this->difficultyPreference = $difficultyPreference;
        return $this;
    }

    public function getTheme(): string
    {
        return $this->theme;
    }

    public function setTheme(string $theme): self
    {
        $this->theme = $theme;
        return $this;
    }

    public function isNotificationsEnabled(): bool
    {
        return $this->notificationsEnabled;
    }

    public function setNotificationsEnabled(bool $notificationsEnabled): self
    {
        $this->notificationsEnabled = $notificationsEnabled;
        return $this;
    }

    public function isAutoAdvance(): bool
    {
        return $this->autoAdvance;
    }

    public function setAutoAdvance(bool $autoAdvance): self
    {
        $this->autoAdvance = $autoAdvance;
        return $this;
    }

    public function isShowExplanations(): bool
    {
        return $this->showExplanations;
    }

    public function setShowExplanations(bool $showExplanations): self
    {
        $this->showExplanations = $showExplanations;
        return $this;
    }

    public function isSoundEnabled(): bool
    {
        return $this->soundEnabled;
    }

    public function setSoundEnabled(bool $soundEnabled): self
    {
        $this->soundEnabled = $soundEnabled;
        return $this;
    }

    public function getPreferencesJson(): ?array
    {
        return $this->preferencesJson;
    }

    public function setPreferencesJson(?array $preferencesJson): self
    {
        $this->preferencesJson = $preferencesJson;
        return $this;
    }
}