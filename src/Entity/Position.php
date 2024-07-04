<?php

namespace App\Entity;

use App\Repository\PositionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'positions')]
#[ORM\Entity(repositoryClass: PositionRepository::class)]
class Position
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Order $buyOrder = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Order $sellOrder = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $status = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBuyOrder(): ?Order
    {
        return $this->buyOrder;
    }

    public function setBuyOrder(?Order $buyOrder): static
    {
        $this->buyOrder = $buyOrder;

        return $this;
    }

    public function getSellOrder(): ?Order
    {
        return $this->sellOrder;
    }

    public function setSellOrder(?Order $sellOrder): static
    {
        $this->sellOrder = $sellOrder;

        return $this;
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
}
