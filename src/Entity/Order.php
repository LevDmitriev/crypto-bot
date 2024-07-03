<?php

namespace App\Entity;

use App\Entity\Order\ByBit\Status as ByBitStatus;
use App\Entity\Order\ByBit\Category;
use App\Entity\Order\ByBit\Side;
use App\Entity\Order\ByBit\Type;
use App\Entity\Order\Status;
use App\Repository\OrderRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: 'orders')]
class Order
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?string $id = null;

    #[ORM\Column(length: 255)]
    private ?float $quantity = null;

    /**
     * Цена в центах
     * @var float|null
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?float $price = null;

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

    #[ORM\Column(nullable: true)]
    private string $status;

    /**
     * Средняя цена выполнения
     * @var float
     */
    #[ORM\Column(nullable: true)]
    private float $averagePrice;

    /**
     * Итоговое купленное количество
     * @var float
     */
    #[ORM\Column(nullable: true)]
    private float $cumulativeExecutedQuantity;

    /**
     * Итоговая стоимость покупки
     * @var float
     */
    #[ORM\Column(nullable: true)]
    private float $cumulativeExecutedValue;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getQuantity(): ?float
    {
        return $this->quantity;
    }

    public function setQuantity(float $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
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
     * @return float
     */
    public function getAveragePrice(): float
    {
        return $this->averagePrice;
    }

    /**
     * @param float $averagePrice
     */
    public function setAveragePrice(float $averagePrice): void
    {
        $this->averagePrice = $averagePrice;
    }

    /**
     * @return float
     */
    public function getCumulativeExecutedQuantity() : float
    {
        return $this->cumulativeExecutedQuantity;
    }

    /**
     * @param float $cumulativeExecutedQuantity
     */
    public function setCumulativeExecutedQuantity(float $cumulativeExecutedQuantity) : void
    {
        $this->cumulativeExecutedQuantity = $cumulativeExecutedQuantity;
    }

    /**
     * @return float
     */
    public function getCumulativeExecutedValue() : float
    {
        return $this->cumulativeExecutedValue;
    }

    /**
     * @param float $cumulativeExecutedValue
     */
    public function setCumulativeExecutedValue(float $cumulativeExecutedValue) : void
    {
        $this->cumulativeExecutedValue = $cumulativeExecutedValue;
    }
}
