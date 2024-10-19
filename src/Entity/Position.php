<?php

namespace App\Entity;

use App\Entity\Order\ByBit\Side;
use App\Entity\Order\Status;
use App\Repository\PositionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Table(name: 'positions')]
#[ORM\Entity(repositoryClass: PositionRepository::class)]
class Position
{
    use TimestampableEntity;
    public function __construct()
    {
        $this->orders = new OrderCollection();
    }

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?string $id = null;

    #[ORM\Column(length: 255, nullable: false)]
    private ?string $status = Status::New->value;

    #[ORM\Column(length: 255, nullable: false)]
    private string $strategyName;

    /**
     * @var Collection
     */
    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'position', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $orders;

    #[ORM\ManyToOne(inversedBy: 'positions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Coin $coin = null;

    #[Gedmo\Timestampable(on: 'change', field: ['id', 'status', 'orders'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    protected $updatedAt = null;


    public function getId(): ?string
    {
        return $this->id;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCoin(): Coin
    {
        return $this->coin;
    }

    /**
     * Получить среднюю цену позиции
     * @return string
     */
    public function getAveragePrice(): string
    {
        $buyOrders = $this->orders->filter(fn (Order $order) => $order->getSide() === Side::Buy);
        assert($buyOrders->count() > 0);
        // Суммируем цены
        $totalPrice = $buyOrders->reduce(fn ($carry, Order $order) => bcadd($carry, $order->getAveragePrice(), 6), '0');
        // Рассчитываем среднюю цену
        return bcdiv($totalPrice, (string) $buyOrders->count(), 6);
    }

    /**
     * Получить общее кол-во непроданных монет
     * @return string
     */
    public function getNotSoldQuantity(): string
    {
        $result = '0';
        foreach ($this->orders as $order) {
            if ($order->isFilled()) {
                switch ($order->getSide()) {
                    case Side::Buy: $result = bcadd($result, $order->getRealExecutedQuantity(), 6);
                        break;
                    case Side::Sell: $result = bcsub($result, $order->getRealExecutedQuantity(), 6);
                        break;
                }
            }
        }
        return $result;
    }

    /**
     * @return OrderCollection
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    /**
     * @return OrderCollection
     */
    public function getOrdersCollection(): Collection
    {
        if ($this->orders instanceof PersistentCollection) {
            return new OrderCollection($this->orders->toArray());
        }

        return $this->orders;
    }

    public function addOrder(Order $order): static
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
            $order->setPosition($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): static
    {
        if ($this->orders->removeElement($order)) {
            // set the owning side to null (unless already changed)
            if ($order->getPosition() === $this) {
                $order->setPosition(null);
            }
        }

        return $this;
    }

    public function setCoin(?Coin $coin): static
    {
        $this->coin = $coin;

        return $this;
    }

    public function getStrategyName(): string
    {
        return $this->strategyName;
    }

    public function setStrategyName(string $strategyName): void
    {
        $this->strategyName = $strategyName;
    }

    /**
     * @return bool
     */
    public function isClosed(): bool
    {
        return $this->status === Position\Status::Closed;
    }

    public function __toString(): string
    {
        return $this->id;
    }
}
