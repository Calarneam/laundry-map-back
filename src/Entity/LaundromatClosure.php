<?php

namespace App\Entity;

use App\Repository\LaundromatClosureRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Enum\Day;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LaundromatClosureRepository::class)]
class LaundromatClosure
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Laundromat::class, inversedBy: 'closures')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Laundromat $laundromat = null;

    #[ORM\Column(enumType: Day::class, length: 50)]
    #[Assert\NotNull]
    #[Assert\Type(Day::class)]
    private ?Day $day = null;

    #[ORM\Column]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $addedDate = null;

    #[ORM\Column]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $startTime = null;

    #[ORM\Column]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $endTime = null;

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

    public function getDay(): ?Day
    {
        return $this->day;
    }

    public function setDay(Day $day): static
    {
        $this->day = $day;

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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getStartTime(): ?\DateTimeImmutable
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeImmutable $startTime): static
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): ?\DateTimeImmutable
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTimeImmutable $endTime): static
    {
        $this->endTime = $endTime;

        return $this;
    }
}
