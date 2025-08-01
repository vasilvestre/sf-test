<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence\Doctrine\ORM\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'user_achievements')]
#[ORM\Index(columns: ['user_id'], name: 'idx_user_achievements_user_id')]
#[ORM\Index(columns: ['earned_at'], name: 'idx_user_achievements_earned_at')]
#[ORM\UniqueConstraint(name: 'unique_user_achievement', columns: ['user_id', 'achievement_id'])]
class UserAchievement
{
    #[ORM\Id]
    #[ORM\Column(type: Types::GUID)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'achievements')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Achievement::class)]
    #[ORM\JoinColumn(name: 'achievement_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Achievement $achievement;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'earned_at')]
    private \DateTimeImmutable $earnedAt;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 100])]
    private int $progress = 100;

    public function __construct(User $user, Achievement $achievement, int $progress = 100)
    {
        $this->user = $user;
        $this->achievement = $achievement;
        $this->progress = $progress;
        $this->earnedAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getAchievement(): Achievement
    {
        return $this->achievement;
    }

    public function setAchievement(Achievement $achievement): self
    {
        $this->achievement = $achievement;
        return $this;
    }

    public function getEarnedAt(): \DateTimeImmutable
    {
        return $this->earnedAt;
    }

    public function setEarnedAt(\DateTimeImmutable $earnedAt): self
    {
        $this->earnedAt = $earnedAt;
        return $this;
    }

    public function getProgress(): int
    {
        return $this->progress;
    }

    public function setProgress(int $progress): self
    {
        $this->progress = $progress;
        return $this;
    }

    public function isCompleted(): bool
    {
        return $this->progress >= 100;
    }
}