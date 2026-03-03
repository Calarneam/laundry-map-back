<?php

namespace App\Entity;

use App\Repository\LaundromatRatingReportRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LaundromatRatingReportRepository::class)]
#[ORM\Table(name: 'laundromat_rating_report')]
class LaundromatRatingReport
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: LaundromatRating::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?LaundromatRating $laundromatRating = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column(length: 50, enumType: ReportReason::class)]
    private ?ReportReason $reason = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    public function getLaundromatRating(): ?LaundromatRating
    {
        return $this->laundromatRating;
    }

    public function setLaundromatRating(?LaundromatRating $laundromatRating): static
    {
        $this->laundromatRating = $laundromatRating;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): static
    {
        $this->date = $date;
        return $this;
    }

    public function getReason(): ?ReportReason
    {
        return $this->reason;
    }

    public function setReason(ReportReason $reason): static
    {
        $this->reason = $reason;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;
        return $this;
    }
}
