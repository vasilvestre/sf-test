<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence\Doctrine\ORM\Entity;

use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\Role;
use App\User\Domain\ValueObject\Username;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
#[ORM\Index(columns: ['email'], name: 'idx_users_email')]
#[ORM\Index(columns: ['username'], name: 'idx_users_username')]
#[ORM\Index(columns: ['email_verified'], name: 'idx_users_email_verified')]
#[ORM\Index(columns: ['last_login_at'], name: 'idx_users_last_login')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: Types::GUID)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private string $id;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    private string $email;

    #[ORM\Column(type: Types::STRING, length: 100, unique: true)]
    private string $username;

    #[ORM\Column(type: Types::STRING, length: 255, name: 'password_hash')]
    private string $password;

    #[ORM\Column(type: Types::JSON)]
    private array $roles = ['ROLE_STUDENT'];

    #[ORM\Column(type: Types::BOOLEAN, name: 'email_verified', options: ['default' => false])]
    private bool $emailVerified = false;

    #[ORM\Column(type: Types::BOOLEAN, name: 'two_factor_enabled', options: ['default' => false])]
    private bool $twoFactorEnabled = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'updated_at')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'last_login_at', nullable: true)]
    private ?\DateTimeImmutable $lastLoginAt = null;

    #[ORM\OneToOne(targetEntity: UserProfile::class, mappedBy: 'user', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private ?UserProfile $profile = null;

    #[ORM\OneToOne(targetEntity: UserPreferences::class, mappedBy: 'user', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private ?UserPreferences $preferences = null;

    #[ORM\OneToMany(targetEntity: UserAchievement::class, mappedBy: 'user', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $achievements;

    #[ORM\OneToMany(targetEntity: StudyPlan::class, mappedBy: 'user', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $studyPlans;

    public function __construct(
        string $email,
        string $username,
        string $password,
        array $roles = ['ROLE_STUDENT']
    ) {
        $this->email = $email;
        $this->username = $username;
        $this->password = $password;
        $this->roles = $roles;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->achievements = new ArrayCollection();
        $this->studyPlans = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerified;
    }

    public function setEmailVerified(bool $verified): self
    {
        $this->emailVerified = $verified;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function isTwoFactorEnabled(): bool
    {
        return $this->twoFactorEnabled;
    }

    public function setTwoFactorEnabled(bool $enabled): self
    {
        $this->twoFactorEnabled = $enabled;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeImmutable $lastLoginAt): self
    {
        $this->lastLoginAt = $lastLoginAt;
        return $this;
    }

    public function getProfile(): ?UserProfile
    {
        return $this->profile;
    }

    public function setProfile(UserProfile $profile): self
    {
        $this->profile = $profile;
        $profile->setUser($this);
        return $this;
    }

    public function getPreferences(): ?UserPreferences
    {
        return $this->preferences;
    }

    public function setPreferences(UserPreferences $preferences): self
    {
        $this->preferences = $preferences;
        $preferences->setUser($this);
        return $this;
    }

    /**
     * @return Collection<int, UserAchievement>
     */
    public function getAchievements(): Collection
    {
        return $this->achievements;
    }

    public function addAchievement(UserAchievement $achievement): self
    {
        if (!$this->achievements->contains($achievement)) {
            $this->achievements->add($achievement);
            $achievement->setUser($this);
        }
        return $this;
    }

    /**
     * @return Collection<int, StudyPlan>
     */
    public function getStudyPlans(): Collection
    {
        return $this->studyPlans;
    }

    public function addStudyPlan(StudyPlan $studyPlan): self
    {
        if (!$this->studyPlans->contains($studyPlan)) {
            $this->studyPlans->add($studyPlan);
            $studyPlan->setUser($this);
        }
        return $this;
    }

    // UserInterface implementation
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }
}