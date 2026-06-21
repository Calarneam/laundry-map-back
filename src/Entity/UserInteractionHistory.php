<?php

namespace App\Entity;

use App\Repository\UserInteractionHistoryRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Enum\UserInteractionAction;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserInteractionHistoryRepository::class)]
class UserInteractionHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    
    #[ORM\ManyToOne(targetEntity: Administrator::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Administrator $administrator = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
<<<<<<< Updated upstream
    #[ORM\JoinColumn(nullable: false)]
=======
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
>>>>>>> Stashed changes
    #[Assert\NotNull]
    private ?User $user = null;

    #[ORM\Column(enumType: UserInteractionAction::class)]
    #[Assert\NotNull]
    private ?UserInteractionAction $action = null;

    #[ORM\Column(length: 255)]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    private ?string $actionReason = null;

    #[ORM\Column]
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getAction(): ?UserInteractionAction
    {
        return $this->action;
    }

    public function setAction(UserInteractionAction $action): static
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
