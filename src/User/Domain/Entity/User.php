<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\Shared\Domain\Entity\AggregateRoot;
use Doctrine\ORM\Mapping as ORM;
use App\Shared\Domain\ValueObject\Id;
use App\User\Domain\Event\UserEmailVerified;
use App\User\Domain\Event\UserPasswordChanged;
use App\User\Domain\Event\UserProfileUpdated;
use App\User\Domain\Event\UserRegistered;
use App\User\Domain\Exception\UserAlreadyVerifiedException;
use App\User\Domain\Exception\UserNotVerifiedException;
use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\Password;
use App\User\Domain\ValueObject\Role;
use App\User\Domain\ValueObject\Username;

/**
 * User aggregate root representing a user in the system.
 */
#[ORM\Entity(repositoryClass: 'App\User\Infrastructure\Persistence\DoctrineUserRepository')]
#[ORM\Table(name: 'users')]
#[ORM\Index(columns: ['email'], name: 'idx_user_email')]
#[ORM\Index(columns: ['username'], name: 'idx_user_username')]
class User extends AggregateRoot
{
    #[ORM\Embedded(class: UserProfile::class)]
    private UserProfile $profile;

    #[ORM\Embedded(class: UserPreferences::class)]
    private UserPreferences $preferences;

    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 320, unique: true)]
    private string $email;

    #[ORM\Column(type: 'string', length: 30, unique: true)]
    private string $username;

    #[ORM\Column(type: 'string', length: 255)]
    private string $password;

    #[ORM\Column(type: 'string', length: 50)]
    private string $role;

    #[ORM\Column(type: 'boolean')]
    private bool $emailVerified;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        Id $id,
        Email $email,
        Username $username,
        Password $password,
        Role $role = new Role(Role::STUDENT),
        bool $emailVerified = false,
        ?\DateTimeImmutable $createdAt = null,
        ?\DateTimeImmutable $updatedAt = null
    ) {
        $this->id = $id->getValue();
        $this->email = $email->getValue();
        $this->username = $username->getValue();
        $this->password = $password->getValue();
        $this->role = $role->getValue();
        $this->emailVerified = $emailVerified;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable();
        $this->profile = new UserProfile($id);
        $this->preferences = new UserPreferences($id);
    }

    public static function register(
        Id $id,
        Email $email,
        Username $username,
        Password $password,
        Role $role = new Role(Role::STUDENT)
    ): self {
        $user = new self($id, $email, $username, $password, $role);
        
        $user->recordEvent(new UserRegistered(
            $id->getValue(),
            $email->getValue(),
            $username->getValue(),
            $role->getValue(),
            new \DateTimeImmutable()
        ));

        return $user;
    }

    public function getId(): Id
    {
        return Id::fromInt($this->id);
    }

    public function getEmail(): Email
    {
        return Email::fromString($this->email);
    }

    public function getUsername(): Username
    {
        return Username::fromString($this->username);
    }

    public function getPassword(): Password
    {
        return Password::fromHash($this->password);
    }

    public function getRole(): Role
    {
        return Role::fromString($this->role);
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerified;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getProfile(): UserProfile
    {
        return $this->profile;
    }

    public function getPreferences(): UserPreferences
    {
        return $this->preferences;
    }

    public function changeEmail(Email $newEmail): void
    {
        if ($this->email === $newEmail->getValue()) {
            return;
        }

        $this->email = $newEmail->getValue();
        $this->emailVerified = false;
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordEvent(new UserProfileUpdated(
            $this->id,
            'email_changed',
            new \DateTimeImmutable()
        ));
    }

    public function changePassword(Password $newPassword): void
    {
        $this->password = $newPassword->getValue();
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordEvent(new UserPasswordChanged(
            $this->id,
            new \DateTimeImmutable()
        ));
    }

    public function changeRole(Role $newRole): void
    {
        if ($this->role === $newRole->getValue()) {
            return;
        }

        $this->role = $newRole->getValue();
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordEvent(new UserProfileUpdated(
            $this->id,
            'role_changed',
            new \DateTimeImmutable()
        ));
    }

    public function verifyEmail(): void
    {
        if ($this->emailVerified) {
            throw new UserAlreadyVerifiedException('User email is already verified');
        }

        $this->emailVerified = true;
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordEvent(new UserEmailVerified(
            $this->id,
            $this->email,
            new \DateTimeImmutable()
        ));
    }

    public function updateProfile(
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $bio = null,
        ?\DateTimeImmutable $dateOfBirth = null
    ): void {
        $this->profile->update($firstName, $lastName, $bio, $dateOfBirth);
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordEvent(new UserProfileUpdated(
            $this->id,
            'profile_updated',
            new \DateTimeImmutable()
        ));
    }

    public function updatePreferences(array $preferences): void
    {
        $this->preferences->update($preferences);
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordEvent(new UserProfileUpdated(
            $this->id,
            'preferences_updated',
            new \DateTimeImmutable()
        ));
    }

    public function hasRole(Role $role): bool
    {
        return $this->getRole()->hasPermission($role);
    }

    public function canTakeQuizzes(): bool
    {
        return $this->emailVerified;
    }

    public function canCreateQuizzes(): bool
    {
        return $this->emailVerified && 
               ($this->getRole()->isInstructor() || $this->getRole()->isAdmin() || $this->getRole()->isSuperAdmin());
    }

    public function canManageUsers(): bool
    {
        return $this->emailVerified && 
               ($this->getRole()->isAdmin() || $this->getRole()->isSuperAdmin());
    }

    public function requireEmailVerification(): void
    {
        if (!$this->emailVerified) {
            throw new UserNotVerifiedException('User email must be verified for this action');
        }
    }
}