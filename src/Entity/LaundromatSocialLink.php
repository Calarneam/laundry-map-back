<?php

namespace App\Entity;

use App\Entity\Enum\SocialLinkType;
use App\Repository\LaundromatSocialLinkRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LaundromatSocialLinkRepository::class)]
#[ORM\UniqueConstraint(name: 'uq_laundromat_social_type', columns: ['laundromat_id', 'type'])]
class LaundromatSocialLink
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Laundromat::class, inversedBy: 'socialLinks')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?Laundromat $laundromat = null;

    #[ORM\Column(length: 20, enumType: SocialLinkType::class)]
    #[Assert\NotNull]
    #[Assert\Type(SocialLinkType::class)]
    private ?SocialLinkType $type = null;

    #[ORM\Column(length: 2048)]
    #[Assert\NotBlank]
    #[Assert\Url(requireTld: true)]
    #[Assert\Length(max: 2048)]
    private ?string $url = null;

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

    public function getType(): ?SocialLinkType
    {
        return $this->type;
    }

    public function setType(SocialLinkType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }
}
