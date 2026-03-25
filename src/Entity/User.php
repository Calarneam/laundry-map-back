<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Entity\Enum\UserStatus;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\Email]
    #[Assert\NotBlank]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $lastName = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 50, enumType: UserStatus::class)]
    #[Assert\NotNull]
    #[Assert\Type(UserStatus::class)]
    private ?UserStatus $status = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $oauthId = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastConnectionDate = null;

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    #[Assert\NotNull]
    private ?Professional $professional = null;

    #[ORM\ManyToMany(targetEntity: Laundromat::class)]
    #[ORM\JoinTable(name: 'user_favorite_laundromat')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'laundromat_id', referencedColumnName: 'id')]
    #[Assert\NotNull]
    private Collection $favoriteLaundromats;

    #[ORM\OneToMany(targetEntity: LaundromatRating::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    #[Assert\NotNull]
    private Collection $ratings;

    public function __construct()
    {
        $this->favoriteLaundromats = new ArrayCollection();
        $this->ratings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        return $this->professional !== null ? ['ROLE_USER', 'ROLE_PROFESSIONAL'] : ['ROLE_USER'];
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getStatus(): ?UserStatus
    {
        return $this->status;
    }

    public function setStatus(UserStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getOauthId(): ?string
    {
        return $this->oauthId;
    }

    public function setOauthId(?string $oauthId): static
    {
        $this->oauthId = $oauthId;

        return $this;
    }

    public function getLastConnectionDate(): ?\DateTimeImmutable
    {
        return $this->lastConnectionDate;
    }

    public function setLastConnectionDate(?\DateTimeImmutable $lastConnectionDate): static
    {
        $this->lastConnectionDate = $lastConnectionDate;

        return $this;
    }

    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = $this->password !== null ? hash('crc32c', $this->password) : '';

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void 
    {
      // nothing to erase
    }

    public function getProfessional(): ?Professional
    {
        return $this->professional;
    }

    public function setProfessional(?Professional $professional): static
    {
        $this->professional = $professional;

        if (null !== $professional && $professional->getUser() !== $this) {
            $professional->setUser($this);
        }

        return $this;
    }

    public function getFavoriteLaundromats(): ?Collection
    {
        return $this->favoriteLaundromats;
    }

    public function setFavoriteLaundromats(?Collection $favoriteLaundromats): static
    {
        $this->favoriteLaundromats = $favoriteLaundromats;

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
}
