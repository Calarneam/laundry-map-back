<?php

namespace App\Service;

use App\Entity\Enum\LaundromatExceptionalClosureType;
use App\Entity\Laundromat;
use App\Entity\LaundromatExceptionalClosure;
use App\Repository\LaundromatRatingRepository;

final class LaundromatSerializer
{
    use LaundromatArrayBuilder;

    public function __construct(
        private readonly LaundromatRatingRepository $laundromatRatingRepository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function serializeDetail(Laundromat $laundromat): array
    {
        $ratingStats = $this->laundromatRatingRepository->getAggregatesForLaundromat(
            (int) $laundromat->getId(),
        );

        $address = $laundromat->getAddress();

        $equipments = [];
        foreach ($laundromat->getEquipments() as $equipment) {
            $equipments[] = [
                'type' => $equipment->getType()->value,
                'capacity' => $equipment->getCapacity(),
                'price' => $equipment->getPrice(),
                'duration' => $equipment->getDuration(),
            ];
        }

        $exceptionalClosures = [];
        foreach ($laundromat->getExceptionalClosures() as $closure) {
            $exceptionalClosures[] = $this->serializeExceptionalClosure($closure);
        }

        return [
            'id' => $laundromat->getId(),
            'establishmentName' => $laundromat->getEstablishmentName(),
            'description' => $laundromat->getDescription(),
            'contactEmail' => $laundromat->getContactEmail(),
            'contactPhone' => $laundromat->getContactPhone(),
            'wiLineReference' => $laundromat->getWiLineReference(),
            'address' => $address ? [
                'fullAddress' => $address->getAddress(),
                'street' => $address->getStreet(),
                'zipCode' => $address->getZipCode(),
                'city' => $address->getCity(),
                'country' => $address->getCountry(),
            ] : null,
            'equipments' => $equipments,
            'services' => $this->buildServiceNames($laundromat),
            'openingHours' => $this->resolveOpeningHours($laundromat),
            'exceptionalClosures' => $exceptionalClosures,
            'paymentMethods' => $this->buildPaymentMethodNames($laundromat),
            'photos' => $this->buildPhotos($laundromat),
            'averageRating' => $ratingStats['averageRating'],
            'ratingCount' => $ratingStats['ratingCount'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeForProfessional(Laundromat $laundromat): array
    {
        $address = $laundromat->getAddress();

        $machines = [];
        foreach ($laundromat->getEquipments() ?? [] as $equipment) {
            $machines[] = [
                'id' => $equipment->getId(),
                'name' => $equipment->getName(),
                'nameTranslationParams' => [
                    'capacity' => $equipment->getCapacity(),
                ],
                'type' => $equipment->getType()?->value,
                'capacity' => $equipment->getCapacity(),
                'price' => $equipment->getPrice(),
                'duration' => $equipment->getDuration(),
                'equipmentReference' => $equipment->getEquipmentReference(),
            ];
        }

        $openingHours = $this->buildOpeningHoursWithIds($laundromat);

        $exceptionalClosures = [];
        foreach ($laundromat->getExceptionalClosures() ?? [] as $closure) {
            $exceptionalClosures[] = $this->serializeExceptionalClosureForProfessional($closure);
        }

        return [
            'id' => $laundromat->getId(),
            'establishmentName' => $laundromat->getEstablishmentName(),
            'description' => $laundromat->getDescription(),
            'contactEmail' => $laundromat->getContactEmail(),
            'contactPhone' => $laundromat->getContactPhone(),
            'wiLineReference' => $laundromat->getWiLineReference(),
            'status' => $laundromat->getStatus()?->value,
            'hasPendingChanges' => $laundromat->hasPendingChanges(),
            'addedDate' => $laundromat->getAddedDate()?->format('Y-m-d'),
            'updatedAt' => $laundromat->getUpdatedAt()?->format('Y-m-d'),
            'address' => [
                'street' => $address?->getStreet(),
                'zipCode' => $address?->getZipCode(),
                'city' => $address?->getCity(),
                'country' => $address?->getCountry(),
                'fullAddress' => $address?->getAddress(),
            ],
            'services' => $this->buildServiceNames($laundromat),
            'paymentMethods' => $this->buildPaymentMethodNames($laundromat),
            'machines' => $machines,
            'openingHours' => $openingHours,
            'isOpenTwentyFourSeven' => $this->isOpenTwentyFourSeven($openingHours),
            'exceptionalClosures' => $exceptionalClosures,
            'photos' => $this->buildPhotos($laundromat),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeExceptionalClosure(LaundromatExceptionalClosure $closure): array
    {
        return [
            'id' => $closure->getId(),
            'type' => $closure->getType()?->value,
            'startDate' => $closure->getStartDate()?->format('Y-m-d\TH:i'),
            'endDate' => $closure->getEndDate()?->format('Y-m-d\TH:i'),
            'reason' => $closure->getReason(),
            'openingHours' => $this->buildSlotOpeningHours($closure),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeExceptionalClosureForProfessional(LaundromatExceptionalClosure $closure): array
    {
        return [
            'id' => $closure->getId(),
            'type' => $closure->getType()?->value,
            'startDate' => $closure->getStartDate()?->format('Y-m-d\TH:i'),
            'endDate' => $closure->getEndDate()?->format('Y-m-d\TH:i'),
            'reason' => $closure->getReason(),
            'openingHours' => $this->buildSlotOpeningHoursWithIds($closure),
        ];
    }

    /**
     * @return list<array{day: string|null, startTime: string|null, endTime: string|null}>
     */
    public function resolveOpeningHours(Laundromat $laundromat): array
    {
        $openingHours = $this->buildOpeningHours($laundromat);
        $activeClosure = $this->findActiveExceptionalClosure($laundromat);

        if (!$activeClosure instanceof LaundromatExceptionalClosure) {
            return $openingHours;
        }

        return match ($activeClosure->getType()) {
            LaundromatExceptionalClosureType::FullClosure => [],
            LaundromatExceptionalClosureType::ModifiedHours => $this->buildSlotOpeningHours($activeClosure),
            default => $openingHours,
        };
    }

    /**
     * @param list<array<string, mixed>> $openingHours
     */
    private function isOpenTwentyFourSeven(array $openingHours): bool
    {
        if (count($openingHours) !== 7) {
            return false;
        }

        foreach ($openingHours as $openingHour) {
            if (
                !isset($openingHour['startTime'], $openingHour['endTime'])
                || $openingHour['startTime'] !== '00:00'
                || $openingHour['endTime'] !== '23:59'
            ) {
                return false;
            }
        }

        return true;
    }

    private function findActiveExceptionalClosure(Laundromat $laundromat): ?LaundromatExceptionalClosure
    {
        $now = new \DateTimeImmutable();

        foreach ($laundromat->getExceptionalClosures() as $closure) {
            $startDate = $closure->getStartDate();
            $endDate = $closure->getEndDate();
            if (
                $startDate instanceof \DateTimeImmutable
                && $endDate instanceof \DateTimeImmutable
                && $startDate <= $now
                && $now <= $endDate
            ) {
                return $closure;
            }
        }

        return null;
    }
}
