<?php

namespace App\Service;

use App\Entity\Laundromat;

final class LaundromatNearbySerializer
{
    public const CACHE_TTL_SECONDS = 180;

    /**
     * @param list<array{laundromat: Laundromat, distanceMeters: float, averageRating?: float|null}> $rows
     *
     * @return list<array<string, mixed>>
     */
    public function serializeRows(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $out[] = $this->serializeOne(
                $row['laundromat'],
                $row['distanceMeters'],
                $row['averageRating'] ?? null,
            );
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeOne(Laundromat $l, float $distanceMeters, ?float $averageRating = null): array
    {
        $address = $l->getAddress();
        $logo = $l->getLogo();

        $openingHours = [];
        foreach ($l->getClosures() ?? [] as $closure) {
            $openingHours[] = [
                'day' => $closure->getDay()?->value,
                'startTime' => $closure->getStartTime()?->format('H:i'),
                'endTime' => $closure->getEndTime()?->format('H:i'),
            ];
        }

        $services = [];
        foreach ($l->getServices() ?? [] as $service) {
            $services[] = $service->getName();
        }

        $paymentMethods = [];
        foreach ($l->getPaymentMethods() ?? [] as $pm) {
            $paymentMethods[] = $pm->getName();
        }

        return [
            'id' => $l->getId(),
            'name' => $l->getEstablishmentName(),
            'machineCount' => $l->getEquipments()->count(),
            'openingHours' => $openingHours,
            'position' => $address?->getPosition(),
            'address' => $address?->getAddress(),
            'image' => $logo?->getLocation(),
            'averageRating' => null !== $averageRating ? round($averageRating, 2) : null,
            'distanceMeters' => round($distanceMeters, 2),
            'services' => $services,
            'paymentMethods' => $paymentMethods,
        ];
    }
}
