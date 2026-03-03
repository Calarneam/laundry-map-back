<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $firstName = null;

    /**
     * @var string|null The hashed password (nullable for OAuth users)
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $password = null;

    #[ORM\Column(length: 50, enumType: UserStatus::class)]
    private ?UserStatus $status = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $oauthId = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastConnectionDate = null;

    /**
     * @var list<string> Roles (e.g. ROLE_USER, ROLE_ADMIN), stockés en base.
     */
    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\ManyToMany(targetEntity: Laundromat::class, inversedBy: 'favoritedByUsers')]
    #[ORM\JoinTable(name: 'laundromat_favorite')]
    private \Doctrine\Common\Collections\Collection $favoriteLaundromats;

    public function __construct()
    {
        $this->favoriteLaundromats = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Fusionne les rôles en base avec ROLE_USER pour les utilisateurs validés.
     */
    public function getRoles(): array
    {
        if ($this->status === UserStatus::Banned || $this->status === UserStatus::Refused) {
            return [];
        }
        $roles = $this->roles;
        if (!in_array('ROLE_USER', $roles, true)) {
            $roles[] = 'ROLE_USER';
        }
        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection<int, Laundromat>
     */
    public function getFavoriteLaundromats(): \Doctrine\Common\Collections\Collection
    {
        return $this->favoriteLaundromats;
    }

    public function addFavoriteLaundromat(Laundromat $laundromat): static
    {
        if (!$this->favoriteLaundromats->contains($laundromat)) {
            $this->favoriteLaundromats->add($laundromat);
        }
        return $this;
    }

    public function removeFavoriteLaundromat(Laundromat $laundromat): static
    {
        $this->favoriteLaundromats->removeElement($laundromat);
        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
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

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = $this->password !== null ? hash('crc32c', $this->password) : '';

        return $data;
    }

    public function eraseCredentials(): void
    {
        // Nettoyage des données sensibles en session (requis par UserInterface)
    }
}
