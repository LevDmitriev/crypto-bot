<?php

namespace App\Entity;

use App\Repository\CoinRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'coins')]
#[ORM\Entity(repositoryClass: CoinRepository::class)]
class Coin
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?string $id = null;

    #[ORM\Column(length: 255, nullable: false)]
    private string $byBitcode;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getByBitcode(): string
    {
        return $this->byBitcode;
    }

    public function setByBitcode(string $byBitcode): static
    {
        $this->byBitcode = $byBitcode;

        return $this;
    }
}
