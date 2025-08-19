<?php

namespace App\Entity;

use App\Repository\ProductRankingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRankingRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ProductRanking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $categoryId = null;

    #[ORM\Column(length: 255)]
    private ?string $categoryName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contextFreeName = null;

    #[ORM\Column]
    private ?int $salesRank = null;

    #[ORM\Column]
    private ?bool $isRoot = false;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $rankingDate = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $updatedAt = null;

    #[ORM\Column]
    private ?bool $isActive = true;

    #[ORM\ManyToOne(inversedBy: 'rankings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTime();
        $this->isActive = true;
        $this->isRoot = false;
        $this->rankingDate = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCategoryId(): ?string
    {
        return $this->categoryId;
    }

    public function setCategoryId(string $categoryId): static
    {
        $this->categoryId = $categoryId;
        return $this;
    }

    public function getCategoryName(): ?string
    {
        return $this->categoryName;
    }

    public function setCategoryName(string $categoryName): static
    {
        $this->categoryName = $categoryName;
        return $this;
    }

    public function getContextFreeName(): ?string
    {
        return $this->contextFreeName;
    }

    public function setContextFreeName(?string $contextFreeName): static
    {
        $this->contextFreeName = $contextFreeName;
        return $this;
    }

    public function getSalesRank(): ?int
    {
        return $this->salesRank;
    }

    public function setSalesRank(int $salesRank): static
    {
        $this->salesRank = $salesRank;
        return $this;
    }

    public function getIsRoot(): ?bool
    {
        return $this->isRoot;
    }

    public function setIsRoot(bool $isRoot): static
    {
        $this->isRoot = $isRoot;
        return $this;
    }

    public function getRankingDate(): ?\DateTime
    {
        return $this->rankingDate;
    }

    public function setRankingDate(\DateTime $rankingDate): static
    {
        $this->rankingDate = $rankingDate;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;
        return $this;
    }
}