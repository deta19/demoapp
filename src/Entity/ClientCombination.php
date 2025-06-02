<?php

namespace App\Entity;

use App\Repository\ClientCombinationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClientCombinationRepository::class)]
class ClientCombination
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $brand = null;

    #[ORM\Column]
    private ?int $Number_of_rooms = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $cooling_capacity = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $indoor_unit_type = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $combinations = null;

    #[ORM\Column]
    private ?\DateTime $date_added = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(string $brand): static
    {
        $this->brand = $brand;

        return $this;
    }

    public function getNumberOfRooms(): ?int
    {
        return $this->Number_of_rooms;
    }

    public function setNumberOfRooms(int $Number_of_rooms): static
    {
        $this->Number_of_rooms = $Number_of_rooms;

        return $this;
    }

    public function getCoolingCapacity(): ?string
    {
        return $this->cooling_capacity;
    }

    public function setCoolingCapacity(string $cooling_capacity): static
    {
        $this->cooling_capacity = $cooling_capacity;

        return $this;
    }

    public function getIndoorUnitType(): ?string
    {
        return $this->indoor_unit_type;
    }

    public function setIndoorUnitType(string $indoor_unit_type): static
    {
        $this->indoor_unit_type = $indoor_unit_type;

        return $this;
    }

    public function getDateAdded(): ?\DateTime
    {
        return $this->date_added;
    }

    public function setDateAdded(\DateTime $date_added): static
    {
        $this->date_added = $date_added;

        return $this;
    }

    public function getCombinations(): ?string
    {
        return $this->combinations;
    }

    public function setCombinations(string $combinations): static
    {
        $this->combinations = $combinations;

        return $this;
    }
}
