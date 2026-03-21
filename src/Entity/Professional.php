<?php

namespace App\Entity;

use App\Repository\ProfessionalRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Enum\ProfessionalStatus;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity(repositoryClass: ProfessionalRepository::class)]
class Professional
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'professional')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private ?int $siren = null;

    #[ORM\Column(length: 255)]
    private ?string $companyName = null;

    #[ORM\Column(length: 10)]
    private ?string $codeApe = null;

    #[ORM\Column(length: 50, enumType: ProfessionalStatus::class)]
    private ?ProfessionalStatus $status = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $validationDate = null;

    #[ORM\OneToOne(targetEntity: Address::class, cascade: ['persist', 'remove'])]
    private ?Address $address = null;

    #[ORM\OneToMany(targetEntity: Laundromat::class, mappedBy: 'professional')]
    private Collection $laundromats;

    public function __construct()
    {
        $this->laundromats = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getSiren(): ?int
    {
        return $this->siren;
    }

    public function setSiren(int $siren): static
    {
        $this->siren = $siren;

        return $this;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(string $companyName): static
    {
        $this->companyName = $companyName;

        return $this;
    }

    public function getCodeApe(): ?string
    {
        return $this->codeApe;
    }

    public function setCodeApe(string $codeApe): static
    {
        $this->codeApe = $codeApe;

        return $this;
    }

    public function getStatus(): ?ProfessionalStatus
    {
        return $this->status;
    }

    public function setStatus(ProfessionalStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getValidationDate(): ?\DateTimeImmutable
    {
        return $this->validationDate;
    }

    public function setValidationDate(?\DateTimeImmutable $validationDate): static
    {
        $this->validationDate = $validationDate;

        return $this;
    }

    public function getAddress(): ?Address
    {
        return $this->address;
    }

    public function setAddress(Address $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getLaundromats(): ?Collection
    {
        return $this->laundromats;
    }

    public function setLaundromats(?Collection $laundromats): static
    {
        $this->laundromats = $laundromats;

        return $this;
    }
}
