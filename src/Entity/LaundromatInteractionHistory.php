<?php

namespace App\Entity;

use App\Repository\LaundromatInteractionHistoryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LaundromatInteractionHistoryRepository::class)]
#[ORM\Table(name: 'laundromat_interaction_history')]
class LaundromatInteractionHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Utilisateur avec rôle admin (ROLE_ADMIN) ayant effectué l'action. */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'administrator_id', referencedColumnName: 'id', nullable: false)]
    private ?User $admin = null;

    #[ORM\ManyToOne(targetEntity: Laundromat::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Laundromat $laundromat = null;

    #[ORM\Column(length: 100)]
    private ?string $action = null;

    #[ORM\Column(length: 255)]
    private ?string $actionReason = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $date = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAdmin(): ?User
    {
        return $this->admin;
    }

    public function setAdmin(?User $admin): static
    {
        $this->admin = $admin;
        return $this;
    }

    public function getLaundromat(): ?Laundromat
    {
        return $this->laundromat;
    }

    public function setLaundromat(?Laundromat $laundromat): static
    {
        $this->laundromat = $laundromat;
        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): static
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
