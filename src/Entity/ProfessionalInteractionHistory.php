<?php

namespace App\Entity;

use App\Repository\ProfessionalInteractionHistoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Enum\ProfessionalInteractionHistoryAction;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProfessionalInteractionHistoryRepository::class)]
class ProfessionalInteractionHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Administrator::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Administrator $administrator = null;

    #[ORM\ManyToOne(targetEntity: Professional::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Professional $professional = null;

    #[ORM\Column(enumType: ProfessionalInteractionHistoryAction::class, length: 50)]
    #[Assert\NotNull]
    #[Assert\Type(ProfessionalInteractionHistoryAction::class)]
    private ?ProfessionalInteractionHistoryAction $action = null;

    #[ORM\Column(length: 255)]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    private ?string $actionReason = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotNull]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $date = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAdministrator(): ?Administrator
    {
        return $this->administrator;
    }

    public function setAdministrator(Administrator $administrator): static
    {
        $this->administrator = $administrator;

        return $this;
    }

    public function getProfessional(): ?Professional
    {
        return $this->professional;
    }

    public function setProfessional(Professional $professional): static
    {
        $this->professional = $professional;

        return $this;
    }

    public function getAction(): ?ProfessionalInteractionHistoryAction
    {
        return $this->action;
    }

    public function setAction(ProfessionalInteractionHistoryAction $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function getActionReason(): ?string
    {
        return $this->actionReason;
    }

    public function setActionReason(string $actionReason): static
    {
        $this->actionReason = $actionReason;

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
}
