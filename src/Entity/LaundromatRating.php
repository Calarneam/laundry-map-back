<?php

namespace App\Entity;

use App\Repository\LaundromatRatingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LaundromatRatingRepository::class)]
class LaundromatRating
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Laundromat::class, inversedBy: 'ratings')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Laundromat $laundromat = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'ratings')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?User $user = null;

    #[ORM\Column(nullable: true)]
    private ?int $rating = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $ratedAt = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Assert\Length(max: 500)]
    private ?string $comment = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $commentedAt = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Assert\Length(max: 500)]
    private ?string $response = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $respondedAt = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Assert\Length(max: 500)]
    private ?string $commentDeletedReason = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $commentDeletedAt = null;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(int $rating): static
    {
        $this->rating = $rating;

        return $this;
    }

    public function getRatedAt(): ?\DateTimeImmutable
    {
        return $this->ratedAt;
    }

    public function setRatedAt(?\DateTimeImmutable $ratedAt): static
    {
        $this->ratedAt = $ratedAt;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function getCommentedAt(): ?\DateTimeImmutable
    {
        return $this->commentedAt;
    }

    public function setCommentedAt(?\DateTimeImmutable $commentedAt): static
    {
        $this->commentedAt = $commentedAt;

        return $this;
    }

    public function getResponse(): ?string
    {
        return $this->response;
    }

    public function setResponse(?string $response): static
    {
        $this->response = $response;

        return $this;
    }

    public function getRespondedAt(): ?\DateTimeImmutable
    {
        return $this->respondedAt;
    }

    public function setRespondedAt(?\DateTimeImmutable $respondedAt): static
    {
        $this->respondedAt = $respondedAt;

        return $this;
    }

    public function getCommentDeletedReason(): ?string
    {
        return $this->commentDeletedReason;
    }

    public function setCommentDeletedReason(string $commentDeletedReason): static
    {
        $this->commentDeletedReason = $commentDeletedReason;

        return $this;
    }

    public function getCommentDeletedAt(): ?\DateTimeImmutable
    {
        return $this->commentDeletedAt;
    }

    public function setCommentDeletedAt(?\DateTimeImmutable $commentDeletedAt): static
    {
        $this->commentDeletedAt = $commentDeletedAt;

        return $this;
    }
}
