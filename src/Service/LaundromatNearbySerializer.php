<?php

namespace App\Service;

use App\Entity\Laundromat;

final class LaundromatNearbySerializer
{
    public const CACHE_TTL_SECONDS = 180;

    /**
     * @param list<array{laundromat: Laundromat, distanceMeters: float}> $rows
     *
     * @return list<array<string, mixed>>
     */
    public function serializeRows(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $out[] = $this->serializeOne($row['laundromat'], $row['distanceMeters']);
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeOne(Laundromat $l, float $distanceMeters): array
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

        return [
            'id' => $l->getId(),
            'name' => $l->getEstablishmentName(),
            'machineCount' => $l->getEquipments()->count(),
            'openingHours' => $openingHours,
            'position' => $address?->getPosition(),
            'address' => $address?->getAddress(),
            'image' => $logo?->getLocation(),
            'distanceMeters' => round($distanceMeters, 2),
        ];
    }
}
