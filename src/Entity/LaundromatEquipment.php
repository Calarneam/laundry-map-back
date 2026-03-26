<?php

namespace App\Entity;

use App\Repository\LaundromatEquipmentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Enum\Equipment;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LaundromatEquipmentRepository::class)]
class LaundromatEquipment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Laundromat::class, inversedBy: 'equipments')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Laundromat $laundromat = null;

    #[ORM\Column(nullable: true)]
    private ?int $equipmentReference = null;

    #[ORM\Column(length: 255)]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\Column(enumType: Equipment::class, length: 50)]
    #[Assert\NotNull]
    #[Assert\Type(Equipment::class)]
    private ?Equipment $type = null;

    #[ORM\Column]
    #[Assert\NotNull]
    private ?int $capacity = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotNull]
    private ?string $price = null;

    #[ORM\Column]
    #[Assert\NotNull]
    private ?int $duration = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLaundromat(): ?Laundromat
    {
        return $this->laundromat;
    }

    public function setLaundromat(Laundromat $laundromat): static
    {
        $this->laundromat = $laundromat;

        return $this;
    }

    public function getEquipmentReference(): ?int
    {
        return $this->equipmentReference;
    }

    public function setEquipmentReference(int $equipmentReference): static
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

    public function getType(): ?Equipment
    {
        return $this->type;
    }

    public function setType(Equipment $type): static
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

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
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
