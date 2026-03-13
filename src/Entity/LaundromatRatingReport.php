<?php

namespace App\Entity;

use App\Repository\LaundromatRatingReportRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Enum\Report;

#[ORM\Entity(repositoryClass: LaundromatRatingReportRepository::class)]
class LaundromatRatingReport
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: LaundromatRating::class)]
    #[ORM\JoinColumn(nullable: false)]
    private LaundromatRating $rating;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column(enumType: Report::class, length: 50)]
    private Report $reason;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment = null;

    public function getRating(): LaundromatRating
    {
        return $this->rating;
    }

    public function setRating(LaundromatRating $rating): static
    {
        $this->rating = $rating;
        
        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
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

    public function getReason(): Report
    {
        return $this->reason;
    }

    public function setReason(Report $reason): static
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
