<?php

namespace App\Entity;

use App\Repository\SocialLinkTypeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SocialLinkTypeRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_SOCIAL_LINK_TYPE_CODE', fields: ['code'])]
class SocialLinkType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    private ?string $code = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private ?string $label = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    private ?string $validationRegex = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getValidationRegex(): ?string
    {
        return $this->validationRegex;
    }

    public function setValidationRegex(string $validationRegex): static
    {
        $this->validationRegex = $validationRegex;

        return $this;
    }
}
