<?php

namespace App\Entity;

use App\Repository\CoinRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Table(name: 'coins')]
#[ORM\Entity(repositoryClass: CoinRepository::class)]
#[UniqueEntity(['byBitCode'])]
class Coin
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?string $id = null;

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
