<?php

namespace App\Service;

use App\Entity\Laundromat;

final class LaundromatNearbySerializer
{
    use LaundromatArrayBuilder;

    public const CACHE_TTL_SECONDS = 180;

    /**
     * @param list<array{laundromat: Laundromat, distanceMeters: float, averageRating?: float|null}> $rows
     *
     * @return list<array<string, mixed>>
     */
    public function serializeRows(array $rows): array
    {
        $serialized = [];
        foreach ($rows as $row) {
            $serialized[] = $this->serializeOne(
                $row['laundromat'],
                $row['distanceMeters'],
                $row['averageRating'] ?? null,
            );
        }

        return $serialized;
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeOne(Laundromat $laundromat, float $distanceMeters, ?float $averageRating = null): array
    {
        $address = $laundromat->getAddress();
        $logo = $laundromat->getLogo();

        return [
            'id' => $laundromat->getId(),
            'name' => $laundromat->getEstablishmentName(),
            'machineCount' => $laundromat->getEquipments()->count(),
            'openingHours' => $this->buildOpeningHours($laundromat),
            'position' => $address?->getPosition(),
            'address' => $address?->getAddress(),
            'image' => $logo?->getLocation(),
            'averageRating' => null !== $averageRating ? round($averageRating, 2) : null,
            'distanceMeters' => round($distanceMeters, 2),
            'services' => $this->buildServiceNames($laundromat),
            'paymentMethods' => $this->buildPaymentMethodNames($laundromat),
        ];
    }
}
