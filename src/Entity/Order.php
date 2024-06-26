<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use App\Entity\Order\Type;
use App\Entity\Order\Side;
use App\Entity\Order\Category;
use Symfony\Bridge\Doctrine\Types\UuidType;
use App\Entity\Order\Status;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: 'orders')]
#[Index(name: 'bybit_id', columns: ['by_bit_id'])]
class Order
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?string $id = null;

    #[ORM\Column(length: 255)]
    private ?string $quantity = null;

    #[ORM\Column(length: 255)]
    private ?string $price = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Coin $coin = null;

    #[ORM\Column(enumType: Type::class, options: ['default' => Type::Market])]
    private ?Type $type = null;

    #[ORM\Column(enumType: Side::class)]
    private ?Side $side = null;

    #[ORM\Column(enumType: Category::class, options: ['default' => Category::spot])]
    private ?Category $category = null;

    #[ORM\Column(enumType: Status::class, options: ['default' => Status::New])]
    private ?Status $status = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getQuantity(): ?string
    {
        return $this->quantity;
    }

    public function setQuantity(string $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getCoin(): ?Coin
    {
        return $this->coin;
    }

    public function setCoin(?Coin $coin): static
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

    public function getSide(): ?Side
    {
        return $this->side;
    }

    public function setSide(Side $side): static
    {
        $this->side = $side;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getStatus(): ?Status
    {
        return $this->status;
    }

    public function setStatus(Status $status): static
    {
        $this->status = $status;

        return $this;
    }
}
