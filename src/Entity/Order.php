<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
#[Index(name: 'bybit_id', columns: ['by_bit_id'])]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $byBitId = null;

    #[ORM\Column(length: 255)]
    private ?string $quantity = null;

    #[ORM\Column(length: 255)]
    private ?string $price = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Coin $coin = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getByBitId(): ?string
    {
        return $this->byBitId;
    }

    public function setByBitId(string $byBitId): static
    {
        $this->byBitId = $byBitId;

        return $this;
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
}
