<?php

namespace App\Entity;

use App\Repository\LaundromatRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LaundromatRepository::class)]
class Laundromat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Professional::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Professional $professional = null;

    #[ORM\Column(length: 50, enumType: LaundromatStatus::class)]
    private ?LaundromatStatus $status = null;

    #[ORM\Column(nullable: true)]
    private ?int $wiLineReference = null;

    #[ORM\ManyToOne(targetEntity: Address::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Address $address = null;

    #[ORM\ManyToOne(targetEntity: Media::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Media $logo = null;

    #[ORM\Column(length: 255)]
    private ?string $establishmentName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contactEmail = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $addedDate = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'favoriteLaundromats')]
    private \Doctrine\Common\Collections\Collection $favoritedByUsers;

    #[ORM\ManyToMany(targetEntity: Service::class, inversedBy: 'laundromats')]
    #[ORM\JoinTable(name: 'laundromat_service')]
    private \Doctrine\Common\Collections\Collection $services;

    #[ORM\ManyToMany(targetEntity: PaymentMethod::class, inversedBy: 'laundromats')]
    #[ORM\JoinTable(name: 'laundromat_payment')]
    private \Doctrine\Common\Collections\Collection $paymentMethods;

    public function __construct()
    {
        $this->favoritedByUsers = new \Doctrine\Common\Collections\ArrayCollection();
        $this->services = new \Doctrine\Common\Collections\ArrayCollection();
        $this->paymentMethods = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProfessional(): ?Professional
    {
        return $this->professional;
    }

    public function setProfessional(?Professional $professional): static
    {
        $this->professional = $professional;
        return $this;
    }

    public function getStatus(): ?LaundromatStatus
    {
        return $this->status;
    }

    public function setStatus(LaundromatStatus $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getWiLineReference(): ?int
    {
        return $this->wiLineReference;
    }

    public function setWiLineReference(?int $wiLineReference): static
    {
        $this->wiLineReference = $wiLineReference;
        return $this;
    }

    public function getAddress(): ?Address
    {
        return $this->address;
    }

    public function setAddress(?Address $address): static
    {
        $this->address = $address;
        return $this;
    }

    public function getLogo(): ?Media
    {
        return $this->logo;
    }

    public function setLogo(?Media $logo): static
    {
        $this->logo = $logo;
        return $this;
    }

    public function getEstablishmentName(): ?string
    {
        return $this->establishmentName;
    }

    public function setEstablishmentName(string $establishmentName): static
    {
        $this->establishmentName = $establishmentName;
        return $this;
    }

    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(?string $contactEmail): static
    {
        $this->contactEmail = $contactEmail;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getAddedDate(): ?\DateTimeImmutable
    {
        return $this->addedDate;
    }

    public function setAddedDate(\DateTimeImmutable $addedDate): static
    {
        $this->addedDate = $addedDate;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): static
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection<int, User>
     */
    public function getFavoritedByUsers(): \Doctrine\Common\Collections\Collection
    {
        return $this->favoritedByUsers;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection<int, Service>
     */
    public function getServices(): \Doctrine\Common\Collections\Collection
    {
        return $this->services;
    }

    public function addService(Service $service): static
    {
        if (!$this->services->contains($service)) {
            $this->services->add($service);
        }
        return $this;
    }

    public function removeService(Service $service): static
    {
        $this->services->removeElement($service);
        return $this;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection<int, PaymentMethod>
     */
    public function getPaymentMethods(): \Doctrine\Common\Collections\Collection
    {
        return $this->paymentMethods;
    }

    public function addPaymentMethod(PaymentMethod $paymentMethod): static
    {
        if (!$this->paymentMethods->contains($paymentMethod)) {
            $this->paymentMethods->add($paymentMethod);
        }
        return $this;
    }

    public function removePaymentMethod(PaymentMethod $paymentMethod): static
    {
        $this->paymentMethods->removeElement($paymentMethod);
        return $this;
    }
}

