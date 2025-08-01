<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\Shared\Domain\ValueObject\Id;
use App\User\Domain\ValueObject\Avatar;
use Doctrine\ORM\Mapping as ORM;

/**
 * User profile entity containing extended user information.
 */
#[ORM\Embeddable]
class UserProfile
{
    #[ORM\Column(type: 'integer')]
    private int $userId;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $firstName;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $lastName;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $bio;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $avatarPath;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateOfBirth;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        Id $userId,
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $bio = null,
        ?Avatar $avatar = null,
        ?\DateTimeImmutable $dateOfBirth = null,
        ?\DateTimeImmutable $updatedAt = null
    ) {
        $this->userId = $userId->getValue();
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->bio = $bio;
        $avatar = $avatar ?? Avatar::none();
        $this->avatarPath = $avatar->getPath();
        $this->dateOfBirth = $dateOfBirth;
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable();
    }

    public function getUserId(): Id
    {
        return Id::fromInt($this->userId);
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function getFullName(): string
    {
        $parts = array_filter([$this->firstName, $this->lastName]);
        return implode(' ', $parts) ?: 'Unknown User';
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function getAvatar(): Avatar
    {
        return $this->avatarPath ? Avatar::fromPath($this->avatarPath) : Avatar::none();
    }

    public function getDateOfBirth(): ?\DateTimeImmutable
    {
        return $this->dateOfBirth;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getAge(): ?int
    {
        if ($this->dateOfBirth === null) {
            return null;
        }

        return $this->dateOfBirth->diff(new \DateTimeImmutable())->y;
    }

    public function update(
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $bio = null,
        ?\DateTimeImmutable $dateOfBirth = null
    ): void {
        $hasChanges = false;

        if ($firstName !== null && $firstName !== $this->firstName) {
            $this->firstName = $this->sanitizeString($firstName);
            $hasChanges = true;
        }

        if ($lastName !== null && $lastName !== $this->lastName) {
            $this->lastName = $this->sanitizeString($lastName);
            $hasChanges = true;
        }

        if ($bio !== null && $bio !== $this->bio) {
            $this->bio = $this->sanitizeBio($bio);
            $hasChanges = true;
        }

        if ($dateOfBirth !== null && $dateOfBirth !== $this->dateOfBirth) {
            $this->validateDateOfBirth($dateOfBirth);
            $this->dateOfBirth = $dateOfBirth;
            $hasChanges = true;
        }

        if ($hasChanges) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function updateAvatar(Avatar $avatar): void
    {
        $this->avatarPath = $avatar->getPath();
        $this->updatedAt = new \DateTimeImmutable();
    }

    private function sanitizeString(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $sanitized = trim($value);
        
        if (strlen($sanitized) > 100) {
            throw new \InvalidArgumentException('Name fields cannot exceed 100 characters');
        }

        return $sanitized;
    }

    private function sanitizeBio(?string $bio): ?string
    {
        if ($bio === null || trim($bio) === '') {
            return null;
        }

        $sanitized = trim($bio);
        
        if (strlen($sanitized) > 1000) {
            throw new \InvalidArgumentException('Bio cannot exceed 1000 characters');
        }

        return $sanitized;
    }

    private function validateDateOfBirth(\DateTimeImmutable $dateOfBirth): void
    {
        $now = new \DateTimeImmutable();
        $age = $dateOfBirth->diff($now)->y;

        if ($dateOfBirth > $now) {
            throw new \InvalidArgumentException('Date of birth cannot be in the future');
        }

        if ($age > 150) {
            throw new \InvalidArgumentException('Invalid date of birth (age cannot exceed 150 years)');
        }

        if ($age < 13) {
            throw new \InvalidArgumentException('Users must be at least 13 years old');
        }
    }
}