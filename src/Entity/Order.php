<?php

namespace App\Entity;

use App\Entity\Order\ByBit\OrderFilter;
use App\Entity\Order\ByBit\Status as ByBitStatus;
use App\Entity\Order\ByBit\Category;
use App\Entity\Order\ByBit\Side;
use App\Entity\Order\ByBit\Type;
use App\Entity\Order\Status;
use App\Repository\PositionRepository;
use Doctrine\DBAL\Types\Types;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: PositionRepository::class)]
#[ORM\Table(name: 'orders')]
class Order
{
    use TimestampableEntity;
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?string $id = null;

    #[ORM\Column(length: 255)]
    private ?string $quantity = null;

    /**
     * Цена
     * @var string|null
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $price = null;
    /**
     * Цена срабатывания условного приказа
     * @var string|null
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $triggerPrice = null;

    /**
     * Цена срабатывания условного приказа
     * @var OrderFilter
     */
    #[ORM\Column(enumType: OrderFilter::class, options: ['default' => OrderFilter::Order])]
    private OrderFilter $orderFilter = OrderFilter::Order;

    /**
     * Цена, при которой будет автоматически выставлен приказ на продажу по рынку.
     * @var string|null
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stopLossPrice = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Coin $coin;

    #[ORM\Column(enumType: Type::class, options: ['default' => Type::Market])]
    private ?Type $type = Type::Market;

    #[ORM\Column(nullable: false, enumType: Side::class)]
    private Side $side;

    #[ORM\Column(enumType: Category::class, options: ['default' => Category::spot])]
    private Category $category = Category::spot;

    #[ORM\Column(enumType: ByBitStatus::class, options: ['default' => ByBitStatus::New])]
    private ByBitStatus $byBitStatus = ByBitStatus::New;

    #[ORM\Column]
    private string $status = "New";

    /**
     * Средняя цена выполнения
     * @var string
     */
    #[ORM\Column(nullable: true)]
    private string $averagePrice;

    /**
     * Итоговое купленное количество без учёта комиссии
     * @var string
     */
    #[ORM\Column(nullable: true)]
    private string $cumulativeExecutedQuantity;

    /**
     * Комиссия, которую взяла биржа. В монете приказа.
     * @var string
     */
    #[ORM\Column(nullable: true)]
    private string $cumulativeExecutedFee;

    /**
     * Итоговая стоимость покупки
     * @var string
     */
    #[ORM\Column(nullable: true)]
    private string $cumulativeExecutedValue;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Position $position = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getQuantity(): ?string
    {
        return $this->quantity;
    }

    public function setQuantity(?string $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(?string $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getCoin(): Coin
    {
        return $this->coin;
    }

    public function setCoin(Coin $coin): static
    {
        $this->coin = $coin;

        return $this;
    }

    public function getType(): ?Type
    {
        return $this->type;
    }

    public function setType(Type $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getSide(): Side
    {
        return $this->side;
    }

    public function setSide(Side $side): static
    {
        $this->side = $side;

        return $this;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function setCategory(Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getByBitStatus(): ?ByBitStatus
    {
        return $this->byBitStatus;
    }

    public function setByBitStatus(ByBitStatus $byBitStatus): static
    {
        $this->byBitStatus = $byBitStatus;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getAveragePrice(): string
    {
        return $this->averagePrice;
    }

    /**
     * @param string $averagePrice
     */
    public function setAveragePrice(string $averagePrice): self
    {
        $this->averagePrice = $averagePrice;

        return $this;
    }

    /**
     * Получить количество купленных монет за вычетом комиссии биржи.
     * @return string
     */
    public function getRealExecutedQuantity(): string
    {
        return bcsub($this->cumulativeExecutedQuantity, $this->cumulativeExecutedFee, 6);
    }

    /**
     * Получить общее количество купленных монет без учёта комиссии биржи.
     * @return string
     */
    public function getCumulativeExecutedQuantity(): string
    {
        return $this->cumulativeExecutedQuantity;
    }

    /**
     * @param string $cumulativeExecutedQuantity
     */
    public function setCumulativeExecutedQuantity(string $cumulativeExecutedQuantity): self
    {
        $this->cumulativeExecutedQuantity = $cumulativeExecutedQuantity;
        return $this;
    }

    /**
     * @return float
     */
    public function getCumulativeExecutedValue(): float
    {
        return $this->cumulativeExecutedValue;
    }

    /**
     * @param string $cumulativeExecutedValue
     */
    public function setCumulativeExecutedValue(string $cumulativeExecutedValue): void
    {
        $this->cumulativeExecutedValue = $cumulativeExecutedValue;
    }

    public function getSymbol(): string
    {
        return $this->getCoin()?->getId() . 'USDT';
    }

    public function getStopLossPrice(): ?string
    {
        return $this->stopLossPrice;
    }

    public function setStopLossPrice(?string $stopLossPrice): static
    {
        $this->stopLossPrice = $stopLossPrice;
        return $this;
    }

    public function getTriggerPrice(): ?string
    {
        return $this->triggerPrice;
    }

    public function setTriggerPrice(?string $triggerPrice): self
    {
        $this->triggerPrice = $triggerPrice;

        return $this;
    }

    public function getOrderFilter(): OrderFilter
    {
        return $this->orderFilter;
    }

    public function setOrderFilter(OrderFilter $orderFilter): self
    {
        $this->orderFilter = $orderFilter;

        return $this;
    }

    public function getPosition(): ?Position
    {
        return $this->position;
    }

    public function setPosition(?Position $position): static
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Является ли стоп-приказом
     * @return bool
     */
    public function isStop(): bool
    {
        return $this->orderFilter === OrderFilter::StopOrder;
    }

    /**
     * Является ли рыночным
     * @return bool
     */
    public function isMarket(): bool
    {
        return $this->type === Type::Market;
    }

    /**
     * Является ли обычным приказом. То есть не стоп-приказом и не TP/SL
     * @return bool
     */
    public function isCommon(): bool
    {
        return $this->getOrderFilter() === OrderFilter::Order;
    }

    /**
     * Полностью выполнен?
     * @return bool
     */
    public function isFilled(): bool
    {
        return $this->getStatus() === Status::Filled->value;
    }

    /**
     * Новый?
     * @return bool
     */
    public function isNew(): bool
    {
        return $this->getStatus() === Status::New->value;
    }

    public function __toString(): string
    {
        $result = [];
        $result[] = match ($this->getSide()) {
            Side::Buy => 'Покупка',
            Side::Sell => 'Продажа',
        };
        $result[] = match ($this->getOrderFilter()) {
            OrderFilter::StopOrder => 'стоп',
            OrderFilter::tpslOrder => 'TP/SL',
            default => ''
        };

        switch ($this->getSide()) {
            case Side::Buy:
                $result[] = "{$this->getRealExecutedQuantity()} {$this->getCoin()->getId()} по {$this->getAveragePrice()} USDT";
                break;
            case Side::Sell:
                $result[] = "{$this->getQuantity()} {$this->getCoin()->getId()} по цене";
                if ($this->isStop()) {
                    $result[] = $this->isFilled() ? $this->getAveragePrice() : $this->getTriggerPrice();
                } else {
                    $result[] = $this->getAveragePrice();
                }
                break;
        }

        $result[] = match ($this->getType()) {
            Type::Market => 'по рынку',
            Type::Limit => 'лимитный',
            default => ''
        };


        return implode(' ', $result);
    }

    public function getCumulativeExecutedFee(): string
    {
        return $this->cumulativeExecutedFee;
    }

    public function setCumulativeExecutedFee(string $cumulativeExecutedFee): self
    {
        $this->cumulativeExecutedFee = $cumulativeExecutedFee;

        return $this;
    }
}
