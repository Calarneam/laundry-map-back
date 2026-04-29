<?php

namespace App\Entity;

use App\Repository\AddressRepository;
use App\Entity\Enum\GeolocationStatus;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AddressRepository::class)]
class Address
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    private ?string $address = null;

    #[ORM\Column(length: 255)]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    private ?string $street = null;

    #[ORM\Column]
    #[Assert\NotNull]
    private ?int $zipCode = null;

    #[ORM\Column(length: 255)]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    private ?string $city = null;

    #[ORM\Column(length: 255)]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    private ?string $country = null;

    /**
     * GeoJSON Point (RFC 7946) : { "type": "Point", "coordinates": [longitude, latitude] }.
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $position = null;

    #[ORM\Column(length: 50, enumType: GeolocationStatus::class)]
    #[Assert\NotNull]
    #[Assert\Type(GeolocationStatus::class)]
    private ?GeolocationStatus $geolocationStatus;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(string $street): static
    {
        $this->street = $street;

        return $this;
    }

    public function getZipCode(): ?int
    {
        return $this->zipCode;
    }

    public function setZipCode(int $zipCode): static
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function getPosition(): ?array
    {
        return $this->position;
    }

    /**
     * @param array{type: string, coordinates: list<float|int|string>}|null $position
     */
    public function setPosition(?array $position): static
    {
        if ($position !== null) {
            if (($position['type'] ?? null) !== 'Point' || !isset($position['coordinates']) || !\is_array($position['coordinates']) || \count($position['coordinates']) < 2) {
                throw new \InvalidArgumentException('position must be a GeoJSON Point with coordinates [longitude, latitude].');
            }
        }
        $this->position = $position;

        return $this;
    }

    /**
     * @return array{type: 'Point', coordinates: array{0: float, 1: float}}
     */
    public static function point(float $longitude, float $latitude): array
    {
        return [
            'type' => 'Point',
            'coordinates' => [$longitude, $latitude],
        ];
    }

    public function getGeolocationStatus(): ?GeolocationStatus
    {
        return $this->geolocationStatus;
    }

    public function setGeolocationStatus(GeolocationStatus $geolocationStatus): static
    {
        $this->geolocationStatus = $geolocationStatus;

        return $this;
    }
}
