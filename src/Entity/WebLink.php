<?php

namespace App\Entity;

use App\Entity\Enum\WebLinkType;
use App\Repository\WebLinkRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: WebLinkRepository::class)]
class WebLink
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Laundromat::class, inversedBy: 'webLinks')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?Laundromat $laundromat = null;

    #[ORM\Id]
    #[ORM\Column(length: 32, enumType: WebLinkType::class)]
    #[Assert\NotNull]
    #[Assert\Type(WebLinkType::class)]
    private ?WebLinkType $type = null;

    #[ORM\Column(length: 255)]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    #[Assert\Url]
    private ?string $url = null;

    public function getLaundromat(): ?Laundromat
    {
        return $this->laundromat;
    }

    public function setLaundromat(Laundromat $laundromat): static
    {
        $this->laundromat = $laundromat;

        return $this;
    }

    public function getType(): ?WebLinkType
    {
        return $this->type;
    }

    public function setType(WebLinkType $type): static
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
