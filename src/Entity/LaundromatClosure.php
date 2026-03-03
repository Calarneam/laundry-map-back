<?php

namespace App\Entity;

use App\Repository\LaundromatClosureRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LaundromatClosureRepository::class)]
#[ORM\Table(name: 'laundromat_closure')]
class LaundromatClosure
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Laundromat::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Laundromat $laundromat = null;

    #[ORM\Column(length: 20, enumType: ClosureDay::class)]
    private ?ClosureDay $day = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $addedDate = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $startTime = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $endTime = null;

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

    public function getDay(): ?ClosureDay
    {
        return $this->day;
    }

    public function setDay(ClosureDay $day): static
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

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeInterface $startTime): static
    {
        $this->startTime = $startTime;
        return $this;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTimeInterface $endTime): static
    {
        $this->endTime = $endTime;
        return $this;
    }
}
