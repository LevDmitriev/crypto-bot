<?php

namespace App\Entity;

use App\Repository\CoinRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Table(name: 'coins')]
#[ORM\Entity(repositoryClass: CoinRepository::class)]
#[UniqueEntity(['byBitCode'])]
class Coin
{
    #[ORM\Id]
    #[ORM\Column(length: 255, nullable: false)]
    private string $code;

    /**
     * @var Collection<int, Position>
     */
    #[ORM\OneToMany(targetEntity: Position::class, mappedBy: 'coin', orphanRemoval: true)]
    private Collection $positions;

    public function __construct()
    {
        $this->positions = new ArrayCollection();
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
    public function __toString(): string
    {
        return $this->code;
    }

    /**
     * @return Collection<int, Position>
     */
    public function getPositions(): Collection
    {
        return $this->positions;
    }

    public function addPosition(Position $position): static
    {
        if (!$this->positions->contains($position)) {
            $this->positions->add($position);
            $position->setCoin($this);
        }

        return $this;
    }

    public function removePosition(Position $position): static
    {
        if ($this->positions->removeElement($position)) {
            // set the owning side to null (unless already changed)
            if ($position->getCoin() === $this) {
                $position->setCoin(null);
            }
        }

        return $this;
    }
}
