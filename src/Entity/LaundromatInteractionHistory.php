<?php

namespace App\Entity;

use App\Repository\LaundromatInteractionHistoryRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Enum\LaundromatInteractionHistoryAction;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LaundromatInteractionHistoryRepository::class)]
class LaundromatInteractionHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Administrator::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Administrator $administrator = null;

    #[ORM\ManyToOne(targetEntity: Laundromat::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?Laundromat $laundromat = null;

    #[ORM\Column(enumType: LaundromatInteractionHistoryAction::class, length: 50)]
    #[Assert\NotNull]
    #[Assert\Type(LaundromatInteractionHistoryAction::class)]
    private ?LaundromatInteractionHistoryAction $action = null;

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

    public function getLaundromat(): ?Laundromat
    {
        return $this->laundromat;
    }

    public function setLaundromat(Laundromat $laundromat): static
    {
        $this->laundromat = $laundromat;

        return $this;
    }

    public function getAction(): ?LaundromatInteractionHistoryAction
    {
        return $this->action;
    }

    public function setAction(LaundromatInteractionHistoryAction $action): static
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
