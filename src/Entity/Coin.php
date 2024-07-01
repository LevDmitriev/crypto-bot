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
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: false)]
    private string $code;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }
}
