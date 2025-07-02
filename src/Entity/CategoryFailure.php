<?php

namespace App\Entity;

use App\Repository\CategoryFailureRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoryFailureRepository::class)]
class CategoryFailure
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    #[ORM\Column]
    private int $failureCount = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getFailureCount(): int
    {
        return $this->failureCount;
    }

    public function setFailureCount(int $failureCount): static
    {
        $this->failureCount = $failureCount;

        return $this;
    }

    public function incrementFailureCount(): static
    {
        $this->failureCount++;

        return $this;
    }
}
