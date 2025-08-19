<?php

namespace App\Entity;

use App\Repository\ProductPriceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductPriceRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ProductPrice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $listingId = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $amount = null;

    #[ORM\Column(length: 3)]
    private ?string $currency = 'EUR';

    #[ORM\Column(length: 50)]
    private ?string $displayAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $savingsAmount = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $savingsDisplay = null;

    #[ORM\Column(nullable: true)]
    private ?int $savingsPercentage = null;

    #[ORM\Column]
    private ?bool $isFreeShipping = false;

    #[ORM\Column]
    private ?bool $violatesMap = false;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $updatedAt = null;

    #[ORM\Column]
    private ?bool $isActive = true;

    #[ORM\ManyToOne(inversedBy: 'prices')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTime();
        $this->isActive = true;
        $this->isFreeShipping = false;
        $this->violatesMap = false;
        $this->currency = 'EUR';
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

    public function getListingId(): ?string
    {
        return $this->listingId;
    }

    public function setListingId(string $listingId): static
    {
        $this->listingId = $listingId;
        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;
        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;
        return $this;
    }

    public function getDisplayAmount(): ?string
    {
        return $this->displayAmount;
    }

    public function setDisplayAmount(string $displayAmount): static
    {
        $this->displayAmount = $displayAmount;
        return $this;
    }

    public function getSavingsAmount(): ?string
    {
        return $this->savingsAmount;
    }

    public function setSavingsAmount(?string $savingsAmount): static
    {
        $this->savingsAmount = $savingsAmount;
        return $this;
    }

    public function getSavingsDisplay(): ?string
    {
        return $this->savingsDisplay;
    }

    public function setSavingsDisplay(?string $savingsDisplay): static
    {
        $this->savingsDisplay = $savingsDisplay;
        return $this;
    }

    public function getSavingsPercentage(): ?int
    {
        return $this->savingsPercentage;
    }

    public function setSavingsPercentage(?int $savingsPercentage): static
    {
        $this->savingsPercentage = $savingsPercentage;
        return $this;
    }

    public function getIsFreeShipping(): ?bool
    {
        return $this->isFreeShipping;
    }

    public function setIsFreeShipping(bool $isFreeShipping): static
    {
        $this->isFreeShipping = $isFreeShipping;
        return $this;
    }

    public function getViolatesMap(): ?bool
    {
        return $this->violatesMap;
    }

    public function setViolatesMap(bool $violatesMap): static
    {
        $this->violatesMap = $violatesMap;
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

    public function getAmountFloat(): float
    {
        return (float)$this->amount;
    }

    public function getSavingsAmountFloat(): float
    {
        return (float)($this->savingsAmount ?? 0);
    }

    public function getOriginalPrice(): float
    {
        return $this->getAmountFloat() + $this->getSavingsAmountFloat();
    }

    public function hasSavings(): bool
    {
        return $this->savingsAmount !== null && $this->getSavingsAmountFloat() > 0;
    }
}