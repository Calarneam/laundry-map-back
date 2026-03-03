<?php

namespace App\Entity;

use App\Repository\LaundromatMediaRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LaundromatMediaRepository::class)]
#[ORM\Table(name: 'laundromat_media')]
class LaundromatMedia
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Laundromat::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Laundromat $laundromat = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Media::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Media $media = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    public function getLaundromat(): ?Laundromat
    {
        return $this->laundromat;
    }

    public function setLaundromat(?Laundromat $laundromat): static
    {
        $this->laundromat = $laundromat;
        return $this;
    }

    public function getMedia(): ?Media
    {
        return $this->media;
    }

    public function setMedia(?Media $media): static
    {
        $this->media = $media;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }
}
