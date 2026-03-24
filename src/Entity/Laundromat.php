<?php

namespace App\Entity;

use App\Repository\LaundromatRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity(repositoryClass: LaundromatRepository::class)]
class Laundromat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Professional::class, inversedBy: 'laundromats')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Professional $professional = null;

    #[ORM\Column(nullable: true)]
    private ?int $wiLineReference = null;

    #[ORM\OneToOne(targetEntity: Address::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Address $address = null;

    #[ORM\OneToOne(targetEntity: Media::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Media $logo = null;

    #[ORM\Column(length: 255)]
    private ?string $establishmentName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contactEmail = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $addedDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    #[ORM\OneToMany(targetEntity: LaundromatMedia::class, mappedBy: 'laundromat', cascade: ['persist', 'remove'])]
    private Collection $medias;

    #[ORM\ManyToMany(targetEntity: Service::class)]
    #[ORM\JoinTable(name: 'laundromat_service')]
    #[ORM\JoinColumn(name: 'laundromat_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'service_id', referencedColumnName: 'id')]
    private Collection $services;

    #[ORM\OneToMany(targetEntity: LaundromatClosure::class, mappedBy: 'laundromat', cascade: ['persist', 'remove'])]
    private Collection $closures;

    #[ORM\OneToMany(targetEntity: LaundromatExceptionalClosure::class, mappedBy: 'laundromat', cascade: ['persist', 'remove'])]
    private Collection $exceptionalClosures;

    #[ORM\ManyToMany(targetEntity: PaymentMethod::class)]
    #[ORM\JoinTable(name: 'laundromat_payment_method')]
    #[ORM\JoinColumn(name: 'laundromat_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'payment_method_id', referencedColumnName: 'id')]
    private Collection $paymentMethods;

    #[ORM\OneToMany(targetEntity: LaundromatRating::class, mappedBy: 'laundromat', cascade: ['persist', 'remove'])]
    private Collection $ratings;

    #[ORM\OneToMany(targetEntity: LaundromatEquipment::class, mappedBy: 'laundromat', cascade: ['persist', 'remove'])]
    private Collection $equipments;

    public function __construct()
    {
        $this->medias = new ArrayCollection();
        $this->services = new ArrayCollection();
        $this->closures = new ArrayCollection();
        $this->exceptionalClosures = new ArrayCollection();
        $this->paymentMethods = new ArrayCollection();
        $this->ratings = new ArrayCollection();
        $this->equipments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function setAddress(Address $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getLogo(): ?Media
    {
        return $this->logo;
    }

    public function setLogo(Media $logo): static
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

    public function setDescription(string $description): static
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

    public function getMedias(): ?Collection
    {
        return $this->medias;
    }

    public function setMedias(?Collection $medias): static
    {
        $this->medias = $medias;

        return $this;
    }

    public function getServices(): ?Collection
    {
        return $this->services;
    }

    public function setServices(?Collection $services): static
    {
        $this->services = $services;

        return $this;
    }

    public function getClosures(): ?Collection
    {
        return $this->closures;

        return $this;
    }

    public function setClosures(?Collection $closures): static
    {
        $this->closures = $closures;

        return $this;
    }

    public function getExceptionalClosures(): ?Collection
    {
        return $this->exceptionalClosures;
    }

    public function setExceptionalClosures(?Collection $exceptionalClosures): static
    {
        $this->exceptionalClosures = $exceptionalClosures;

        return $this;
    }

    public function getPaymentMethods(): ?Collection
    {
        return $this->paymentMethods;
    }

    public function setPaymentMethods(?Collection $paymentMethods): static
    {
        $this->paymentMethods = $paymentMethods;

        return $this;
    }

    public function getRatings(): ?Collection
    {
        return $this->ratings;
    }

    public function setRatings(?Collection $ratings): static
    {
        $this->ratings = $ratings;

        return $this;
    }

    public function getEquipments(): ?Collection
    {
        return $this->equipments;
    }
    
    public function setEquipments(?Collection $equipments): static
    {
        $this->equipments = $equipments;

        return $this;
    }
}
