<?php

namespace App\Entity;

use App\Repository\LaundromatEquipmentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LaundromatEquipmentRepository::class)]
#[ORM\Table(name: 'laundromat_equipment')]
class LaundromatEquipment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Laundromat::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Laundromat $laundromat = null;

    #[ORM\Column(nullable: true)]
    private ?int $equipmentReference = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 50, enumType: EquipmentType::class)]
    private ?EquipmentType $type = null;

    #[ORM\Column]
    private ?int $capacity = null;

    #[ORM\Column]
    private ?float $price = null;

    #[ORM\Column]
    private ?int $duration = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLaundromat(): ?Laundromat
    {
        return $this->laundromat;
    }

    public function setLaundromat(?Laundromat $laundromat): static
    {
        $this->laundromat = $laundromat;
        return $this;
    }

    public function getEquipmentReference(): ?int
    {
        return $this->equipmentReference;
    }

    public function setEquipmentReference(?int $equipmentReference): static
    {
        $this->equipmentReference = $equipmentReference;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getType(): ?EquipmentType
    {
        return $this->type;
    }

    public function setType(EquipmentType $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    public function setCapacity(int $capacity): static
    {
        $this->capacity = $capacity;
        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): static
    {
        $this->duration = $duration;
        return $this;
    }
}
