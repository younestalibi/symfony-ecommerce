<?php

namespace App\Entity;

use App\Enum\OrderStatus;
use App\Repository\OrderRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
#[HasLifecycleCallbacks]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(enumType: OrderStatus::class)]
    private ?OrderStatus $status = OrderStatus::PENDING;

    #[ORM\Column]
    private ?float $total = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $paymentIntentId = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $reference = null;

    /**
     * @var Collection<int, OrderItem>
     */
    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'productOrder', orphanRemoval: true)]
    private Collection $orderItems;

    #[ORM\Column(length: 255)]
    private ?string $shippingLine1 = null;

    #[ORM\Column(length: 255)]
    private ?string $shippingLine2 = null;

    #[ORM\Column(length: 30)]
    private ?string $shippingCity = null;

    #[ORM\Column(length: 30)]
    private ?string $shippingCountry = null;

    #[ORM\Column(length: 30)]
    private ?string $shippingZipCode = null;

    #[ORM\Column]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->orderItems = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getStatus(): ?OrderStatus
    {
        return $this->status;
    }

    public function setStatus(OrderStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getTotal(): ?float
    {
        return $this->total;
    }

    public function setTotal(float $total): static
    {
        $this->total = $total;

        return $this;
    }

    public function getPaymentIntentId(): ?string
    {
        return $this->paymentIntentId;
    }

    public function setPaymentIntentId(?string $paymentIntentId): static
    {
        $this->paymentIntentId = $paymentIntentId;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function getOrderItems(): Collection
    {
        return $this->orderItems;
    }

    public function addOrderItem(OrderItem $orderItem): static
    {
        if (!$this->orderItems->contains($orderItem)) {
            $this->orderItems->add($orderItem);
            $orderItem->setProductOrder($this);
        }

        return $this;
    }

    public function removeOrderItem(OrderItem $orderItem): static
    {
        if ($this->orderItems->removeElement($orderItem)) {
            // set the owning side to null (unless already changed)
            if ($orderItem->getProductOrder() === $this) {
                $orderItem->setProductOrder(null);
            }
        }

        return $this;
    }

    public function getShippingLine1(): ?string
    {
        return $this->shippingLine1;
    }

    public function setShippingLine1(string $shippingLine1): static
    {
        $this->shippingLine1 = $shippingLine1;

        return $this;
    }

    public function getShippingLine2(): ?string
    {
        return $this->shippingLine2;
    }

    public function setShippingLine2(string $shippingLine2): static
    {
        $this->shippingLine2 = $shippingLine2;

        return $this;
    }

    public function getShippingCity(): ?string
    {
        return $this->shippingCity;
    }

    public function setShippingCity(string $shippingCity): static
    {
        $this->shippingCity = $shippingCity;

        return $this;
    }

    public function getShippingCountry(): ?string
    {
        return $this->shippingCountry;
    }

    public function setShippingCountry(string $shippingCountry): static
    {
        $this->shippingCountry = $shippingCountry;

        return $this;
    }

    public function getShippingZipCode(): ?string
    {
        return $this->shippingZipCode;
    }

    public function setShippingZipCode(string $shippingZipCode): static
    {
        $this->shippingZipCode = $shippingZipCode;

        return $this;
    }


    #[ORM\PrePersist]
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    // -- Lifecycle callback to create update updatedAt/createdAt on create
    #[ORM\PrePersist]
    public function setCreatedAt(): void
    {
        $this->createdAt = new DateTimeImmutable();
        $this->setUpdatedAt();
    }
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
    // -- Lifecycle callback to  update updatedAt on update
    #[ORM\PreUpdate]
    public function setUpdatedAt(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
