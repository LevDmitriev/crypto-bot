<?php

namespace App\Entity;

use App\Repository\CoinRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'coins')]
#[ORM\Entity(repositoryClass: CoinRepository::class)]
class Coin
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?string $id = null;

    #[Assert\Unique]
    #[ORM\Column(length: 255, nullable: false)]
    private string $byBitCode;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getByBitCode(): string
    {
        return $this->byBitCode;
    }

    public function setByBitCode(string $byBitCode): static
    {
        $this->byBitCode = $byBitCode;

        return $this;
    }
    public function __toString(): string
    {
        return $this->byBitCode;
    }
}
