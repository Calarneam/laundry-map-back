<?php

namespace App\Entity;

use App\Repository\PaymentMethodRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaymentMethodRepository::class)]
#[ORM\Table(name: 'payment_method')]
class PaymentMethod
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToMany(targetEntity: Laundromat::class, mappedBy: 'paymentMethods')]
    private \Doctrine\Common\Collections\Collection $laundromats;

    public function __construct()
    {
        $this->laundromats = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection<int, Laundromat>
     */
    public function getLaundromats(): \Doctrine\Common\Collections\Collection
    {
        return $this->laundromats;
    }
}
