<?php

namespace App\Entity;

use App\Entity\Enum\LaundromatExceptionalClosureType;
use App\Repository\LaundromatExceptionalClosureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LaundromatExceptionalClosureRepository::class)]
class LaundromatExceptionalClosure
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Laundromat::class, inversedBy: 'exceptionalClosures')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Laundromat $laundromat = null;

<<<<<<< Updated upstream
    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotNull]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotNull]
    #[Assert\DateTime]
=======
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Assert\NotNull]
>>>>>>> Stashed changes
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column(enumType: LaundromatExceptionalClosureType::class, length: 50)]
    #[Assert\NotNull]
    #[Assert\Type(LaundromatExceptionalClosureType::class)]
    private ?LaundromatExceptionalClosureType $type = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $reason = null;

<<<<<<< Updated upstream
    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotNull]
    #[Assert\DateTime]
=======
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Assert\NotNull]
>>>>>>> Stashed changes
    private ?\DateTimeImmutable $addedDate = null;

    #[ORM\OneToMany(targetEntity: LaundromatExceptionalClosureSlot::class, mappedBy: 'exceptionalClosure', cascade: ['persist', 'remove'])]
    #[Assert\NotNull]
    private Collection $openingHours;

    public function __construct()
    {
        $this->openingHours = new ArrayCollection();
    }

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

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(string $reason): static
    {
        $this->reason = $reason;

        return $this;
    }

    public function getType(): ?LaundromatExceptionalClosureType
    {
        return $this->type;
    }

    public function setType(LaundromatExceptionalClosureType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getAddedDate(): ?\DateTimeImmutable
    {
        return $this->addedDate;
    }

    public function setAddedDate(\DateTimeImmutable $addedDate): static
    {
        $this->addedDate = $addedDate;

        return $this;
    }

    public function getOpeningHours(): Collection
    {
        return $this->openingHours;
    }

    public function setOpeningHours(Collection $openingHours): static
    {
        $this->openingHours = $openingHours;

        return $this;
    }
}
