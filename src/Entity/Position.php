<?php

namespace App\Entity;

use App\Entity\Order\ByBit\Status as ByBitStatus;
use App\Entity\Position\Status;
use App\Repository\PositionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'positions')]
#[ORM\Entity(repositoryClass: PositionRepository::class)]
class Position
{
    public function __construct(Order $buyOrder)
    {
        // Позиция может быть создана только на полностью выполненном приказе на покупку
        assert($buyOrder->getByBitStatus() === ByBitStatus::Filled);
        $this->buyOrder = $buyOrder;
        $this->coin = $buyOrder->getCoin();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private Order $buyOrder;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private Order $stopOrder;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Order $sellOrder = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $status = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBuyOrder(): Order
    {
        return $this->buyOrder;
    }

    public function setBuyOrder(Order $buyOrder): static
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

    public function getCoin(): Coin
    {
        return $this->getBuyOrder()->getCoin();
    }

    public function isOpened(): bool
    {
        return $this->getSellOrder()?->getStatus() !== Status::Closed && $this->getStopOrder()?->getStatus() !== Status::Closed;
    }

    public function isClosed(): bool
    {
        return $this->getSellOrder()?->getStatus() == Status::Closed || $this->getStopOrder()?->getStatus() == Status::Closed;
    }

    public function getStopOrder(): Order
    {
        return $this->stopOrder;
    }

    public function setStopOrder(Order $stopOrder): void
    {
        $this->stopOrder = $stopOrder;
    }
}
